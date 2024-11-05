<!DOCTYPE html>
<html>

<head>
    <title>Quote Details</title>
</head>

<body>
    <h1>Quotation Details</h1>
    <b>Dear {{ $name ?? "" }},</b>
    <p>Here are the details of your quote:</p>
    <ul>
        <li><strong>Document Number:</strong> {{ $emailData['document_number'] }}</li>
        <li><strong>Reference:</strong> {{ $emailData['reference'] }}</li>
        <li><strong>Date:</strong> {{ $emailData['date'] }}</li>
        <li><strong>Expiring Date:</strong> {{ $emailData['expiring_date'] }}</li>
        <li><strong>Sales Representative:</strong> {{ $emailData['sales_rep'] }}</li>
        {{-- <li><strong>Currency:</strong> {{ $emailData['currency'] }}</li> --}}
    </ul>
    <h2>Items</h2>
    <ul>
        @foreach ($emailData['items'] as $item)
            <li>
                <strong>Item:</strong> {{ $item['item'] }}<br>
                <strong>Quantity:</strong> {{ $item['quantity'] }}<br>
                <strong>Amount:</strong> {{ $item['amount'] }}<br>
                <strong>Discount:</strong> {{ $item['discount'] }}<br>
            </li>
        @endforeach
    </ul>


    {{-- @if ($filePath)
    <h2>Supporting Document</h2>
    <img src="{{ $message->embed(storage_path('app/' . $filePath)) }}" alt="Supporting Document">
    @endif --}}
    <p>Thank you for your business!</p>
</body>

</html>
