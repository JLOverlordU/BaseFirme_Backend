<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Sales\Sale;
use App\Models\Products\Product;
use App\Models\Administrable\ClientProvider;
use App\Http\Traits\Parametrizaciones\Respuestas\SendResponse;

class ReportsController extends Controller{

    public function getStatistics(Request $request) {
        
        try {

            $filters = $request->all();
            $year = $filters["year"] ?? date('Y');

            $sales = $this->getSalesPurchasesPerMonth('sales', $year);
            $purchases = $this->getSalesPurchasesPerMonth('purchases', $year);
            $salesPerClient = $this->getSalesPerClient('sales','client_id', $year);
            $salesPerProvider = $this->getSalesPerClient('purchases', 'provider_id', $year);

            $data = [
                "sales_per_month" => $sales,
                "purchases_per_month" => $purchases,
                "sales_per_client" => $salesPerClient,
                "purchases_per_client" => $salesPerProvider
            ];

            return SendResponse::message(true, 'store', 'Estadística generadas correctamente', $data, 200);
    
        } catch (\Throwable $th) {
            return SendResponse::message(false, 'reports', 'Error al obtener las estadísticas.', $th->getMessage(), 500);
        }

    }

    public function getSalesPurchasesPerMonth($table = 'sales', $year) {
        
        try {

            $salesPurchasesPerMonth = array_fill(0, 12, 0);
    
            $query = DB::table($table)
                        ->select(DB::raw('MONTH(date) as month, COUNT(*) as count'))
                        ->where('status', 'activo');
            
            if($year != "") {
                $query->whereYear('date', $year);
            }

            $salesPurchases = $query->groupBy('month')
                                    ->orderBy('month')
                                    ->get();
    
            foreach ($salesPurchases as $salePurchase) {
                $salesPurchasesPerMonth[$salePurchase->month - 1] = $salePurchase->count;
            }

            return $salesPurchasesPerMonth;
    
        } catch (\Throwable $th) {
            return [];
        }

    }

    public function getSalesPerClient($table, $field, $year) {

        try {

            $query = DB::table($table)
                        ->join('clients_providers', "{$table}.{$field}", '=', 'clients_providers.id')
                        ->select('clients_providers.name', DB::raw('COUNT(' . $table . '.id) as sales_count'));

            if($year != "") {
                $query->whereYear("{$table}.date", $year);
            }

            $data = $query
                        ->groupBy('clients_providers.name')
                        ->orderBy('sales_count', 'desc')
                        ->get();
    
            $names  = $data->pluck('name')->toArray();
            $counts = $data->pluck('sales_count')->toArray();
    
            return [
                "names" => $names,
                "counts" => $counts,
            ];

        } catch (\Throwable $th) {
            return [];
        }

    }

    public function getNotifications() {

        try {

            $data = [];
            $daysLimit = 8;
            $products = Product::where('status', 'activo')
                                ->where(function ($query) {
                                    $query->whereHas('unitMeasure', function ($q) {
                                        $q->where('slug', 'kg');
                                    })
                                    ->whereColumn('converted_stock', '<', 'minimum_quantity')
                                    ->orWhere(function ($q) {
                                        $q->whereHas('unitMeasure', function ($q) {
                                            $q->where('slug', '!=', 'kg');
                                        })
                                        ->whereColumn('stock', '<', 'minimum_quantity');
                                    });
                                })
                                ->with('unitMeasure')
                                ->get();

            foreach ($products as $product) {
                $data[] = "Se acaba el stock del producto " . $product->name;
            }

            $clients = ClientProvider::where("type", "client")
                    ->where(function ($query) use ($daysLimit) {
                    $query->whereHas('depositsClients', function ($q) use ($daysLimit) {
                        $q->whereHas('sale', function ($saleQuery) {
                            $saleQuery->whereColumn('subtotal', '!=', 'deposit');
                        })->whereDate('created_at', '<=', Carbon::now()->subDays($daysLimit));
                    });
                })
                ->with(['depositsClients.sale'])
                ->get();

            foreach ($clients as $client) {
                $data[] = "El cliente " . $client->name . " tiene depósito pendiente";
            }

            $providers = ClientProvider::where("type", "provider")
                    ->where(function ($query) use ($daysLimit) {
                    $query->whereHas('depositsProviders', function ($q) use ($daysLimit) {
                        $q->whereHas('purchase', function ($purchaseQuery) {
                            $purchaseQuery->whereColumn('subtotal', '!=', 'deposit');
                        })->whereDate('created_at', '<=', Carbon::now()->subDays($daysLimit));
                    });
                })
                ->with(['depositsProviders.purchase'])
                ->get();

            foreach ($providers as $provider) {
                $data[] = "El proveedor " . $provider->name . " tiene depósito pendiente";
            }

            return SendResponse::message(true, 'store', 'Notifiaciones generadas correctamente', $data, 200);

        } catch (\Throwable $th) {
            return SendResponse::message(false, 'reports', 'Error al obtener las notificaciones.', $th->getMessage(), 500);
        }

    }

}
