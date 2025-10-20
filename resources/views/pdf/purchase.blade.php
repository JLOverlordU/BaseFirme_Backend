<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: 'Helvetica', sans-serif; 
            font-size: 14px;
            color: #000; 
            margin: 0; 
            padding: 0; 
            width: 100%; 
            height: auto;
            overflow: hidden;
        }
    
        .header { 
            font-size: 18px; 
            background-color: #D3D3D3; 
            color: #000; 
            padding: 5px; 
            text-align: center; 
            font-weight: bold; 
            width: 100%;
        }
    
        .info-container, .info-column, .section-title, .totals { 
            width: 100%; 
            text-align: left;
            margin-top: 5px;
        }
    
        .info-column p { 
            margin: 0;
        }
    
        .info-column p span { 
            font-weight: bold;
        }

        .section-title { 
            font-weight: bold;
            margin-bottom: 3px;
        }

        .table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 5px;
            table-layout: fixed; 
        }
    
        .table th, .table td { 
            border: 1px solid #000; 
            padding: 2px; 
            text-align: center; 
            font-size: 12px; 
            word-wrap: break-word; 
        }

        .table th { 
            background-color: #A9A9A9; 
            color: #000;
        }

        .right { 
            text-align: right;
        }

        .totals { 
            margin-top: 5px; 
            font-size: 14px;
        }

        .bold { 
            font-weight: bold;
        }
    
        @media print {
            body {
                width: 100%; 
                margin: 0; 
                padding: 0; 
                overflow: hidden;
            }
    
            .table {
                width: 100%;
                font-size: 12px;
            }

            .totals {
                font-size: 14px;
            }
        }
    </style>
    <title>{{ $purchase->boleta_factura == "boleta" ? "Boleta de Compra" : "Factura" }} N° {{ $purchase->consecutive }}</title>
</head>
<body>
    <div class="header">PENCASPAMPA</div>
    <br>

    <!-- Información de la boleta -->
    <div class="info-column">
        <p>
            <span>RUC:</span> {{ $purchase->boleta_factura == "boleta" ? $provider->document : $purchase->ruc }}
        </p>
        <p><span>N° de {{ $purchase->boleta_factura == "boleta" ? "Boleta" : "Factura" }}:</span> {{ $purchase->consecutive }}</p>
        <p><span>Tipo de Compra:</span> {{ $purchase->type == "contado" ? "Contado" : "Crédito" }}</p>
        <p><span>Fecha:</span> {{ \Carbon\Carbon::parse($purchase->date)->format('d/m/Y') }}</p>
    </div>

    <!-- Información del comprador -->
    <div class="info-container">
        <div class="info-column">
            <br>
            <div class="section-title">Información del Comprador</div>
            <p><span>Nombre:</span> {{ $user->name }}</p>
            <p><span>Teléfono:</span> {{ $user->phone }}</p>
            <p><span>Email:</span> {{ $user->email }}</p>
        </div>
        <br>

        <!-- Información del proveedor -->
        <div class="info-column">
            <div class="section-title">Información del Proveedor</div>
            <p><span>Nombre:</span> {{ $provider->name }}</p>
            <p><span>Dirección:</span> {{ $provider->address }}</p>
        </div>
    </div>
    <br>

    <!-- Detalles de la compra -->
    <div class="section-title">Detalles de la Compra</div>
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
        <p class="right">Subtotal: S/. {{ number_format($purchase->subtotal, 4) }}</p>

        @if($purchase->type == "credito")
            <p class="right">Depósito: S/. {{ number_format($purchase->deposit, 4) }}</p>
        @endif

        <p class="right bold">Total a Pagar: S/. {{ number_format($purchase->total, 4) }}</p>
    </div>
</body>
</html>
