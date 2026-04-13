@php
    $ps = $bill->payment_status;
    $order = $bill->order;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bill->invoice_number }} — {{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 1.25rem;
            color: #18181b;
            font-size: 14px;
            line-height: 1.5;
        }
        h1 {
            font-size: 1.25rem;
            margin: 0 0 0.25rem;
        }
        h2 {
            font-size: 1rem;
            margin: 1.25rem 0 0.5rem;
            border-bottom: 1px solid #e4e4e7;
            padding-bottom: 0.25rem;
        }
        .muted { color: #71717a; }
        .row { display: flex; justify-content: space-between; gap: 1rem; margin: 0.2rem 0; }
        .tabular { font-variant-numeric: tabular-nums; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        th, td { text-align: left; padding: 0.4rem 0.5rem; border-bottom: 1px solid #e4e4e7; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #71717a; }
        td.end, th.end { text-align: right; }
        .totals { margin-top: 0.75rem; max-width: 16rem; margin-left: auto; }
        .totals .row { font-weight: 600; }
        .no-print {
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .no-print button, .no-print a {
            font: inherit;
            padding: 0.45rem 0.85rem;
            border-radius: 0.5rem;
            border: 1px solid #d4d4d8;
            background: #fafafa;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .no-print button.primary {
            background: #0ea5e9;
            border-color: #0284c7;
            color: #fff;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="primary" onclick="window.print()">{{ __('Print') }}</button>
        <a href="{{ route('orders.billing.show', $bill) }}">{{ __('Back to invoice') }}</a>
    </div>

    <header>
        <h1>{{ config('app.name') }}</h1>
        <p class="muted" style="margin:0">{{ __('Invoice / receipt') }}</p>
    </header>

    <h2>{{ __('Bill details') }}</h2>
    <div class="row"><span class="muted">{{ __('Internal invoice #') }}</span><span class="tabular">{{ $bill->invoice_number }}</span></div>
    @if(filled($bill->official_receipt_number))
        <div class="row"><span class="muted">{{ __('Official receipt (OR) #') }}</span><span class="tabular">{{ $bill->official_receipt_number }}</span></div>
    @endif
    <div class="row"><span class="muted">{{ __('Status') }}</span><span>{{ $bill->payment_status->label() }}</span></div>
    <div class="row"><span class="muted">{{ __('Date') }}</span><span class="tabular">{{ $bill->created_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span></div>
    @if($bill->paid_at)
        <div class="row"><span class="muted">{{ __('Paid at') }}</span><span class="tabular">{{ $bill->paid_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span></div>
    @endif
    @if($bill->payment_method)
        <div class="row"><span class="muted">{{ __('Payment method') }}</span><span>{{ $bill->payment_method->label() }}</span></div>
    @endif

    @if($order)
        <h2>{{ __('Customer & order') }}</h2>
        <div class="row"><span class="muted">{{ __('Order #') }}</span><span class="tabular">{{ $order->order_number }}</span></div>
        @if($order->isWalkIn())
            <div class="row"><span class="muted">{{ __('Customer') }}</span><span>{{ $order->walk_in_name ?? '—' }}</span></div>
            @if(filled($order->walk_in_phone))
                <div class="row"><span class="muted">{{ __('Phone') }}</span><span class="tabular">{{ $order->walk_in_phone }}</span></div>
            @endif
        @else
            <div class="row"><span class="muted">{{ __('Customer') }}</span><span>{{ $order->user?->name ?? '—' }}</span></div>
            @if(filled($order->user?->phone))
                <div class="row"><span class="muted">{{ __('Phone') }}</span><span class="tabular">{{ $order->user->phone }}</span></div>
            @endif
        @endif

        @if($order->relationLoaded('items') && $order->items->isNotEmpty())
            <h2>{{ __('Line items') }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Item') }}</th>
                        <th class="end">{{ __('Qty') }}</th>
                        <th class="end">{{ __('Unit') }}</th>
                        <th class="end">{{ __('Subtotal') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $line)
                        @php
                            $name = $line->productVariant?->product?->name ?? __('Product');
                            $sku = $line->productVariant?->sku;
                            if (filled($sku)) {
                                $name .= ' ('.$sku.')';
                            }
                        @endphp
                        <tr>
                            <td>{{ $name }}</td>
                            <td class="end tabular">{{ $line->quantity }}</td>
                            <td class="end tabular">{{ \App\Support\Money::peso($line->unit_price) }}</td>
                            <td class="end tabular">{{ \App\Support\Money::peso($line->subtotal) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <h2>{{ __('Amounts') }}</h2>
    @if(in_array($ps, [\App\Enums\PaymentStatus::Voided, \App\Enums\PaymentStatus::Refunded, \App\Enums\PaymentStatus::PartiallyRefunded], true))
        <p class="muted">{{ __('This document is not an outstanding invoice.') }}</p>
    @endif
    <div class="totals">
        <div class="row"><span>{{ __('Invoice total') }}</span><span class="tabular">{{ \App\Support\Money::peso($bill->amount) }}</span></div>
        <div class="row"><span>{{ in_array($ps, [\App\Enums\PaymentStatus::Refunded, \App\Enums\PaymentStatus::PartiallyRefunded], true) ? __('Net amount paid') : __('Amount paid') }}</span><span class="tabular">{{ \App\Support\Money::peso($bill->amount_paid) }}</span></div>
        @if(! in_array($ps, [\App\Enums\PaymentStatus::Voided, \App\Enums\PaymentStatus::Refunded, \App\Enums\PaymentStatus::PartiallyRefunded], true))
            <div class="row"><span>{{ __('Balance due') }}</span><span class="tabular">{{ \App\Support\Money::peso($bill->balance_due) }}</span></div>
        @endif
    </div>

    @if(filled($order?->notes))
        <h2>{{ __('Notes') }}</h2>
        <p style="white-space: pre-wrap; margin: 0">{{ $order->notes }}</p>
    @endif

    <p class="muted" style="margin-top: 2rem; font-size: 12px;">
        {{ __('Thank you for your business.') }}
    </p>
</body>
</html>
