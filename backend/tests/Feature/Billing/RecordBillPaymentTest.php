<?php

namespace Tests\Feature\Billing;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Bill;
use App\Models\Order;
use App\Services\BillingService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecordBillPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->decimal('amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);
            $table->string('payment_status')->default(PaymentStatus::Unpaid->value);
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('bills');
        Schema::dropIfExists('orders');

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
}
