<?php

namespace Tests\Feature\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\BillingPaymentHistory;
use App\Models\Order;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordBillPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->default(UserRole::Customer->value);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('walk_in_name')->nullable();
            $table->string('walk_in_phone')->nullable();
            $table->string('order_number')->unique();
            $table->string('status')->default(OrderStatus::Pending->value);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->string('official_receipt_number')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);
            $table->string('payment_status')->default(PaymentStatus::Unpaid->value);
            $table->string('payment_method')->nullable();
            $table->unsignedBigInteger('collected_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_payment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('authorized_by_user_id')->nullable();
            $table->string('action', 48);
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('from_payment_status', 32)->nullable();
            $table->string('to_payment_status', 32);
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('billing_payment_histories');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_first_payment_marks_bill_as_partially_paid(): void
    {
        $bill = $this->createUnpaidBill(1000);

        $updated = app(BillingService::class)->recordPayment($bill, 500, 'cash');

        $this->assertSame(PaymentStatus::PartiallyPaid, $updated->payment_status);
        $this->assertSame('500.00', $updated->amount_paid);
        $this->assertSame('500.00', $updated->balance_due);
        $this->assertNull($updated->paid_at);
    }

    public function test_second_payment_marks_bill_as_paid(): void
    {
        $bill = $this->createUnpaidBill(1000);
        $service = app(BillingService::class);

        $service->recordPayment($bill, 500, 'cash');
        $updated = $service->recordPayment($bill->fresh(), 500, 'cash');

        $this->assertSame(PaymentStatus::Paid, $updated->payment_status);
        $this->assertSame('1000.00', $updated->amount_paid);
        $this->assertSame('0.00', $updated->balance_due);
        $this->assertNotNull($updated->paid_at);
    }

    public function test_ensure_bill_for_order_creates_unpaid_bill_from_order_total(): void
    {
        $order = Order::query()->create([
            'user_id' => null,
            'order_number' => 'ORD-ENSURE-'.fake()->unique()->numerify('#####'),
            'status' => OrderStatus::Pending,
            'total_amount' => 888.25,
        ]);

        $bill = app(BillingService::class)->ensureBillForOrder($order);

        $this->assertSame($order->id, $bill->order_id);
        $this->assertSame('888.25', $bill->amount);
        $this->assertSame('888.25', $bill->balance_due);
        $this->assertSame(PaymentStatus::Unpaid, $bill->payment_status);
        $this->assertNotEmpty($bill->invoice_number);
    }

    public function test_ensure_bill_for_order_is_idempotent(): void
    {
        $order = Order::query()->create([
            'user_id' => null,
            'order_number' => 'ORD-IDEMP-'.fake()->unique()->numerify('#####'),
            'status' => OrderStatus::Pending,
            'total_amount' => 100,
        ]);

        $service = app(BillingService::class);
        $first = $service->ensureBillForOrder($order);
        $second = $service->ensureBillForOrder($order);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Bill::query()->where('order_id', $order->id)->count());
    }

    public function test_overpayment_is_rejected(): void
    {
        $bill = $this->createUnpaidBill(1000);

        try {
            app(BillingService::class)->recordPayment($bill, 1000.01, 'cash');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment_amount', $exception->errors());
        }

        $this->assertDatabaseHas('bills', [
            'id' => $bill->id,
            'payment_status' => PaymentStatus::Unpaid->value,
            'amount_paid' => '0.00',
            'balance_due' => '1000.00',
        ]);
    }

    public function test_void_unpaid_bill_sets_balance_due_to_zero(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'role' => UserRole::Admin,
            'email' => 'admin-bill-test@example.test',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);

        $bill = $this->createUnpaidBill(750);
        $updated = app(BillingService::class)->void($bill, $admin);

        $this->assertSame(PaymentStatus::Voided, $updated->payment_status);
        $this->assertSame('0.00', $updated->balance_due);
    }

    public function test_full_refund_records_history_and_clears_amount_paid(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin Refund',
            'role' => UserRole::Admin,
            'email' => 'admin-refund-full@example.test',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);

        $bill = $this->createPaidBill(800);
        $service = app(BillingService::class);

        $updated = $service->refund(
            $bill,
            $admin,
            800,
            PaymentMethod::GCash,
            $admin->id,
            'Customer return',
        );

        $this->assertSame(PaymentStatus::Refunded, $updated->payment_status);
        $this->assertSame('0.00', $updated->amount_paid);
        $this->assertSame('0.00', $updated->balance_due);
        $this->assertNull($updated->paid_at);

        $this->assertDatabaseHas('billing_payment_histories', [
            'bill_id' => $bill->id,
            'action' => BillingPaymentHistory::ACTION_REFUNDED,
            'amount' => '800.00',
            'payment_method' => PaymentMethod::GCash->value,
            'authorized_by_user_id' => $admin->id,
            'to_payment_status' => PaymentStatus::Refunded->value,
        ]);
    }

    public function test_partial_refund_sets_partially_refunded_and_reduces_amount_paid(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin Partial',
            'role' => UserRole::Admin,
            'email' => 'admin-refund-partial@example.test',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);

        $bill = $this->createPaidBill(1000);
        $service = app(BillingService::class);

        $updated = $service->refund(
            $bill,
            $admin,
            250,
            PaymentMethod::Cash,
            $admin->id,
            'Restocking fee retained',
        );

        $this->assertSame(PaymentStatus::PartiallyRefunded, $updated->payment_status);
        $this->assertSame('750.00', $updated->amount_paid);
        $this->assertSame('0.00', $updated->balance_due);

        $this->assertDatabaseHas('billing_payment_histories', [
            'bill_id' => $bill->id,
            'action' => BillingPaymentHistory::ACTION_REFUNDED,
            'amount' => '250.00',
            'to_payment_status' => PaymentStatus::PartiallyRefunded->value,
        ]);
    }

    public function test_second_refund_from_partially_refunded_can_finish_full_reversal(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin Two Step',
            'role' => UserRole::Admin,
            'email' => 'admin-refund-two@example.test',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);

        $bill = $this->createPaidBill(600);
        $service = app(BillingService::class);
        $service->refund($bill, $admin, 200, PaymentMethod::Cash, $admin->id, 'First');
        $final = $service->refund($bill->fresh(), $admin, 400, PaymentMethod::Maya, $admin->id, 'Second');

        $this->assertSame(PaymentStatus::Refunded, $final->payment_status);
        $this->assertSame('0.00', $final->amount_paid);
    }

    private function createUnpaidBill(float $amount): Bill
    {
        $order = Order::query()->create([
            'user_id' => null,
            'order_number' => 'ORD-TEST-'.fake()->unique()->numerify('#####'),
            'status' => OrderStatus::Pending,
            'total_amount' => $amount,
        ]);

        return Bill::query()->create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-'.fake()->unique()->numerify('#####'),
            'amount' => $amount,
            'amount_paid' => 0,
            'balance_due' => $amount,
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    private function createPaidBill(float $amount): Bill
    {
        $order = Order::query()->create([
            'user_id' => null,
            'order_number' => 'ORD-TEST-'.fake()->unique()->numerify('#####'),
            'status' => OrderStatus::Pending,
            'total_amount' => $amount,
        ]);

        return Bill::query()->create([
            'order_id' => $order->id,
            'invoice_number' => 'INV-TEST-'.fake()->unique()->numerify('#####'),
            'amount' => $amount,
            'amount_paid' => $amount,
            'balance_due' => 0,
            'payment_status' => PaymentStatus::Paid,
            'payment_method' => PaymentMethod::Cash->value,
            'paid_at' => now(),
        ]);
    }
}
