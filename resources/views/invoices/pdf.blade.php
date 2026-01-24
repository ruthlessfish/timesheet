<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 32px;
            margin: 0 0 5px 0;
            color: #000;
        }
        .invoice-number {
            color: #666;
            font-size: 14px;
        }
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-sent { background-color: #d1ecf1; color: #0c5460; }
        .status-draft { background-color: #e2e3e5; color: #383d41; }
        .status-overdue { background-color: #f8d7da; color: #721c24; }
        .status-cancelled { background-color: #fff3cd; color: #856404; }
        
        .info-section {
            margin-bottom: 30px;
        }
        .info-grid {
            width: 100%;
        }
        .info-grid td {
            vertical-align: top;
            width: 50%;
        }
        .info-title {
            font-size: 10px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .info-content {
            line-height: 1.6;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table thead {
            background-color: #f8f9fa;
        }
        .items-table th {
            padding: 10px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            border-bottom: 2px solid #dee2e6;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table .item-description {
            font-size: 12px;
        }
        .items-table .item-date {
            font-size: 10px;
            color: #666;
        }
        
        .totals-section {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        .totals-row {
            padding: 8px 0;
            overflow: hidden;
        }
        .totals-row.subtotal,
        .totals-row.tax {
            font-size: 12px;
        }
        .totals-row.total {
            border-top: 2px solid #000;
            font-size: 16px;
            font-weight: bold;
            padding-top: 12px;
            margin-top: 5px;
        }
        .totals-label {
            float: left;
        }
        .totals-amount {
            float: right;
        }
        
        .notes-section {
            clear: both;
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .notes-title {
            font-size: 10px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .notes-content {
            line-height: 1.6;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1>INVOICE</h1>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <span class="status status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-section">
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-title">Bill To</div>
                    <div class="info-content">
                        <strong>{{ $invoice->client->name }}</strong><br>
                        @if($invoice->client->company)
                            {{ $invoice->client->company }}<br>
                        @endif
                        @if($invoice->client->email)
                            {{ $invoice->client->email }}<br>
                        @endif
                        @if($invoice->client->address)
                            {!! nl2br(e($invoice->client->address)) !!}
                        @endif
                    </div>
                </td>
                <td style="text-align: right;">
                    <div class="info-title">Invoice Details</div>
                    <div class="info-content">
                        <strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}<br>
                        <strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Description</th>
                <th class="text-right" style="width: 15%;">Hours</th>
                <th class="text-right" style="width: 15%;">Rate</th>
                <th class="text-right" style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>
                    <div class="item-description">{{ $item->description }}</div>
                    @if($item->timeEntry)
                        <div class="item-date">{{ $item->timeEntry->start_time->format('M d, Y') }}</div>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-right">${{ number_format($item->rate, 2) }}</td>
                <td class="text-right"><strong>${{ number_format($item->amount, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="totals-row subtotal">
            <div class="totals-label">Subtotal:</div>
            <div class="totals-amount">${{ number_format($invoice->subtotal, 2) }}</div>
        </div>
        <div class="totals-row tax">
            <div class="totals-label">Tax ({{ $invoice->tax_rate }}%):</div>
            <div class="totals-amount">${{ number_format($invoice->tax_amount, 2) }}</div>
        </div>
        <div class="totals-row total">
            <div class="totals-label">Total:</div>
            <div class="totals-amount">${{ number_format($invoice->total, 2) }}</div>
        </div>
    </div>

    @if($invoice->notes)
    <div class="notes-section">
        <div class="notes-title">Notes</div>
        <div class="notes-content">{{ $invoice->notes }}</div>
    </div>
    @endif
</body>
</html>
