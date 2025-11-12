<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1f2937;
            font-size: 12px;
        }

        .header,
        .footer {
            text-align: center;
        }

        .header h1 {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .header p {
            margin: 2px 0;
        }

        .section {
            margin-top: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            text-align: left;
        }

        th {
            background-color: #f8fafc;
        }

        .amount {
            font-size: 16px;
            font-weight: 600;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>AINET - 9th International Conference 2026</h1>
        <p>Gateway Education, Sonipat, Delhi NCR</p>
        <p>Email: theainet@gmail.com | Website: https://ainet.co.in</p>
    </div>

    <div class="section">
        <table>
            <tr>
                <th>Invoice Number</th>
                <td>{{ $invoiceNumber }}</td>
                <th>Invoice Date</th>
                <td>{{ $paidAt->timezone(config('app.timezone'))->format('F j, Y g:i A') }}</td>
            </tr>
            <tr>
                <th>Razorpay Order ID</th>
                <td>{{ $orderId }}</td>
                <th>Razorpay Payment ID</th>
                <td>{{ $paymentId }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Bill To</h3>
        <table>
            <tr>
                <th>Name</th>
                <td>{{ $drf->pre_title }} {{ $drf->name }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $drf->email }}</td>
            </tr>
            <tr>
                <th>Institution</th>
                <td>{{ $drf->institution }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>{{ $drf->address }}, {{ $drf->city }}, {{ $drf->state }} {{ $drf->pincode }}</td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>{{ $drf->country_code ? '+' . ltrim($drf->country_code, '+') . ' ' : '' }}{{ $drf->phone_no }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Registration Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Delegate Registration Fee - {{ $drf->you_are_register_as }}
                        @if ($drf->member === 'Yes')
                            <br><small>Includes AINET Member 20% discount (if applicable)</small>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($amount, 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="text-right amount">₹ {{ number_format($amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="section">
        <p><strong>Notes:</strong></p>
        <ul>
            <li>Please retain this invoice for your records.</li>
            <li>For any queries, reach out to the conference team at <a href="mailto:theainet@gmail.com">theainet@gmail.com</a>.</li>
        </ul>
    </div>

    <div class="footer">
        <p>Thank you for being a part of AINET 2026!</p>
    </div>
</body>

</html>

