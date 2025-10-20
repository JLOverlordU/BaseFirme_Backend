<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 12px; /* Ajustar el tamaño de letra para tickets */
            color: #000; 
            width: 80mm; /* Ancho del papel térmico */
            margin: 0; 
            padding: 0; 
        }

        .header { 
            font-size: 18px; /* Ajustar el tamaño de la cabecera */
            background-color: #D3D3D3; 
            color: #000; 
            padding: 5px; 
            text-align: center; 
            font-weight: bold; 
            width: 100%; /* Ocupa todo el ancho */
            box-sizing: border-box; /* Incluye el padding dentro del ancho */
        }

        .info-container { 
            display: block; 
            text-align: left; 
            margin: 5px 0; 
            width: 100%; 
        }

        .info-column { 
            width: 100%; 
            margin-bottom: 5px; 
        }

        .info-column p { 
            margin: 2px 0; /* Reducir el margen entre líneas */
        }

        .info-column p span { 
            font-weight: bold; 
        }

        .section-title { 
            font-weight: bold; 
            margin-bottom: 3px; 
            font-size: 12px; 
        }

        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 5px; 
            table-layout: fixed; /* Evita que las columnas se expandan demasiado */
        }

        .table th, .table td { 
            border: 1px solid #000; 
            padding: 2px; /* Reducir el padding */
            text-align: center; 
            font-size: 11px; /* Reducir un poco el tamaño de la letra */
            word-wrap: break-word; /* Ajuste de texto en caso de desbordamiento */
        }

        .table th { 
            background-color: #A9A9A9; 
            color: #000; 
        }
    
    
        .table td {
            word-wrap: break-word; /* Asegura que el texto no desborde */
        }
    

        .table td {
            word-wrap: break-word; /* Asegura que el texto no desborde */
        }
    
        .totals { 
            margin-top: 5px; 
            font-size: 12px; 
        }

        .right { 
            text-align: right; 
        }

        .bold { 
            font-weight: bold; 
        }

        @media print {
            body {
                width: 80mm; /* Mantener el ancho del ticket */
                height: 210mm; /* Altura estándar del ticket */
                margin: 0;
                padding: 0;
            }

            .header, .info-container, .table, .totals {
                width: 100%; /* Mantener el ancho completo */
            }

            .table {
                font-size: 11px; /* Tamaño de letra reducido para los detalles de la tabla */
            }

            .totals {
                font-size: 12px; /* Asegura que los totales se vean claramente */
            }
        }
    </style>
    <title>{{ $sale->boleta_factura == "boleta" ? "Boleta de Venta" : "Factura" }} N° {{ $sale->consecutive }}</title>
</head>
<body>

    <div class="header">PENCASPAMPA</div>

    <!-- Información de la boleta -->
    <div class="info-column">
        <br>
        <p><span>N° de {{ $sale->boleta_factura == "boleta" ? "Boleta" : "Factura" }}:</span> {{ $sale->consecutive }}</p>
        <p><span>Tipo de Venta:</span> {{ $sale->type == "contado" ? "Contado" : "Crédito" }}</p>
        <p><span>Fecha:</span> {{ \Carbon\Carbon::parse($sale->date)->format('d/m/Y') }}</p>
    </div>

    <!-- Información del vendedor -->
    <div class="info-container">
        <div class="info-column">
            <br>
            <div class="section-title">Información del vendedor</div>
            <p><span>Vendedor:</span> {{ $user->name }}</p>
            <p><span>Teléfono:</span> {{ $user->phone }}</p>
            <p><span>Email:</span> {{ $user->email }}</p>
        </div>

        <!-- Información del cliente -->
        <div class="info-column">
            <br>
            <div class="section-title">Información del cliente</div>
            <p>
                <span>{{ $sale->boleta_factura == "boleta" ? "DNI" : "RUC" }}:</span> {{ $sale->boleta_factura == "boleta" ? $client->document : $sale->ruc }}
            </p>
            <p><span>Nombre:</span> {{ $client->name }}</p>
            <p><span>Dirección:</span> {{ $client->address }}</p>
        </div>
    </div>

    <!-- Detalles de la venta -->
    <br>
    <div class="section-title">Detalles de la Venta</div>
    <table class="table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Producto</th>
                <th>Cant.</th>
                <th>P.U. (S/.)</th>
                <th>Importe (S/.)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name }}</td>
                    <td>{{ number_format($detail->amount, 2) }} {{ $detail->name_unit_measure }}</td>
                    <td>{{ number_format($detail->price, 2) }}</td>
                    <td>{{ number_format($detail->amount * $detail->price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="totals">
        <p class="right">Subtotal: S/. {{ number_format($sale->subtotal, 2) }}</p>

        @if($sale->type == "credito")
            <p class="right">Depósito: S/. {{ number_format($sale->deposit, 2) }}</p>
        @endif

        <p class="right bold">Total a Pagar: S/. {{ number_format($sale->total, 2) }}</p>
    </div>

</body>
</html>