<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraping Status</title>
    <style>
        body {
            font-family: sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Scraping Status</h1>

    <h2>Collections</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>URL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($collections as $collection)
                <tr>
                    <td>{{ $collection->id }}</td>
                    <td>{{ $collection->name }}</td>
                    <td><a href="{{ $collection->url }}">{{ $collection->url }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Products</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Image</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->price }}</td>
                    <td><img src="{{ $product->image }}" alt="{{ $product->name }}" width="100"></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
