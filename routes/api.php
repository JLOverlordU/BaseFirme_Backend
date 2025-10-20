<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UnitMeasureController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\FormulaController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//* Login
    Route::post("/login", [LoginController::class, 'login']);
    Route::post("/logout", [LoginController::class, 'logout']);
    Route::get("/get_user", [LoginController::class, 'getUser']);
    // Route::middleware(['auth:api', 'web'])->get('/get_user', [LoginController::class, 'getUser']);

//* Maestras

    //? Presentations
    Route::post("/presentations", [PresentationController::class, 'index']);
    Route::post("/presentation", [PresentationController::class, 'store']);
    Route::post("/presentation/{id}", [PresentationController::class, 'show']);
    Route::delete("/presentation/{id}", [PresentationController::class, 'destroy']);
    Route::put("/presentation/{id}", [PresentationController::class, 'update']);

    //? Processes
    Route::post("/processes", [ProcessController::class, 'index']);
    Route::post("/process", [ProcessController::class, 'store']);
    Route::post("/process/{id}", [ProcessController::class, 'show']);
    Route::delete("/process/{id}", [ProcessController::class, 'destroy']);
    Route::put("/process/{id}", [ProcessController::class, 'update']);

    //? Shifts
    Route::post("/shifts", [ShiftController::class, 'index']);
    Route::post("/shift", [ShiftController::class, 'store']);
    Route::post("/shift/{id}", [ShiftController::class, 'show']);
    Route::delete("/shift/{id}", [ShiftController::class, 'destroy']);
    Route::put("/shift/{id}", [ShiftController::class, 'update']);

    //? Units Measure
    Route::post("/units_measure", [UnitMeasureController::class, 'index']);
    Route::post("/unit_measure", [UnitMeasureController::class, 'store']);
    Route::post("/units_measure_convert", [UnitMeasureController::class, 'getUnitsMeasureConvert']);
    Route::post("/unit_measure_convert", [UnitMeasureController::class, 'getUnitMeasureConvert']);
    Route::post("/unit_measure/{id}", [UnitMeasureController::class, 'show']);
    Route::delete("/unit_measure/{id}", [UnitMeasureController::class, 'destroy']);
    Route::put("/unit_measure/{id}", [UnitMeasureController::class, 'update']);
    Route::post("/convertion", [UnitMeasureController::class, 'saveConvertion']);
    Route::post("/convertions", [UnitMeasureController::class, 'getConvertionsByUnitMeasure']);
    Route::delete("/convertion/{id}", [UnitMeasureController::class, 'destroyConvertion']);

    //? Machines
    Route::post("/machines", [MachineController::class, 'index']);
    Route::post("/machine", [MachineController::class, 'store']);
    Route::post("/machine/{id}", [MachineController::class, 'show']);
    Route::delete("/machine/{id}", [MachineController::class, 'destroy']);
    Route::put("/machine/{id}", [MachineController::class, 'update']);
    
//* Administrable

    //? Roles
    Route::post("/roles", [RoleController::class, 'index']);
    Route::post("/role_permission", [RoleController::class, 'getRolePermission']);
    Route::post("/functions", [RoleController::class, 'getFunctions']);
    Route::post("/change_function", [RoleController::class, 'changeFunction']);
    Route::post("/rol", [RoleController::class, 'store']);
    Route::post("/rol/{id}", [RoleController::class, 'show']);
    Route::delete("/rol/{id}", [RoleController::class, 'destroy']);
    Route::put("/rol/{id}", [RoleController::class, 'update']);

    //? User
    Route::post("/users", [UserController::class, 'index']);
    Route::post("/user", [UserController::class, 'store']);
    Route::post("/user/{id}", [UserController::class, 'show']);
    Route::delete("/user/{id}", [UserController::class, 'destroy']);
    Route::put("/user/{id}", [UserController::class, 'update']);

    //? Client
    Route::post("/clients", [ClientController::class, 'index']);
    Route::post("/client", [ClientController::class, 'store']);
    Route::post("/client/{id}", [ClientController::class, 'show']);
    Route::delete("/client/{id}", [ClientController::class, 'destroy']);
    Route::put("/client/{id}", [ClientController::class, 'update']);

    //? Provider
    Route::post("/providers", [ProviderController::class, 'index']);
    Route::post("/provider", [ProviderController::class, 'store']);
    Route::post("/provider/{id}", [ProviderController::class, 'show']);
    Route::delete("/provider/{id}", [ProviderController::class, 'destroy']);
    Route::put("/provider/{id}", [ProviderController::class, 'update']);

//* Products

    //? Product
    Route::get("/products", [ProductController::class, 'index']);
    Route::post("/products", [ProductController::class, 'list']);
    Route::post("/product", [ProductController::class, 'store']);
    Route::post("/transfer", [ProductController::class, 'transfer']);
    Route::post("/stock", [ProductController::class, 'stock']);
    Route::post("/history_stock", [ProductController::class, 'getProductStockHistory']);
    Route::post("/product/{id}", [ProductController::class, 'show']);
    Route::delete("/product/{id}", [ProductController::class, 'destroy']);
    Route::put("/product/{id}", [ProductController::class, 'update']);
    
//* Productions

    //? Production
    Route::post("/productions", [ProductionController::class, 'index']);
    Route::post("/production", [ProductionController::class, 'store']);
    Route::post("/production_client", [ProductionController::class, 'store_client']);
    Route::post("/production/{id}", [ProductionController::class, 'show']);
    Route::post("/productions_excel", [ProductionController::class, 'excelProductions']);
    Route::post("/productions_details_excel", [ProductionController::class, 'excelDetailsProductions']);
    Route::post("/productions_products_excel", [ProductionController::class, 'excelProductionsProducts']);
    Route::post("/productions_shifts_excel", [ProductionController::class, 'excelProductionsShifts']);
    Route::post("/productions_processes_excel", [ProductionController::class, 'excelProductionsProcesses']);
    Route::delete("/production/{id}", [ProductionController::class, 'destroy']);
    Route::put("/production/{id}", [ProductionController::class, 'update']);
    Route::put("/production_client/{id}", [ProductionController::class, 'update']);
    
//* Fórmulas

    //? Fórmula
    Route::post("/formulas", [FormulaController::class, 'index']);
    Route::post("/formula", [FormulaController::class, 'store']);
    Route::post("/formula_excel", [FormulaController::class, 'formulaExcel']);
    Route::post("/formula/{id}", [FormulaController::class, 'show']);
    Route::delete("/formula/{id}", [FormulaController::class, 'destroy']);
    Route::put("/formula/{id}", [FormulaController::class, 'update']);

    //? Detalles Fórmula    
    Route::delete("/formula_details/{id}", [FormulaController::class, 'destroyDetails']);

//* Ventas

    //? Venta
    Route::get("/sales", [SaleController::class, 'index']);
    Route::post("/sales", [SaleController::class, 'getSales']);
    Route::post("/sales_details", [SaleController::class, 'getSalesDetails']);
    Route::post("/sales_by_client", [SaleController::class, 'getClientBySales']);
    Route::post("/sale_deposit", [SaleController::class, 'saveDepositsHistory']);
    Route::post("/sales_deposits_history", [SaleController::class, 'getDepositsHistory']);
    Route::delete("/sale_deposit/{id}", [SaleController::class, 'destroyDeposit']);
    Route::post("/sale", [SaleController::class, 'store']);
    Route::post("/sales_details_excel", [SaleController::class, 'excelSalesDetails']);
    Route::post("/sales_excel", [SaleController::class, 'excelSales']);
    Route::post('/sale_excel', [SaleController::class, 'excelSale']);
    Route::post('/sale_pdf', [SaleController::class, 'pdfSale']);
    Route::post("/sale/{id}", [SaleController::class, 'show']);
    Route::delete("/sale/{id}", [SaleController::class, 'destroy']);
    Route::put("/sale/{id}", [SaleController::class, 'update']);

    //? Detalles Venta    
    Route::delete("/sale_details/{id}", [SaleController::class, 'destroyDetails']);

//* Compras

    //? Compra
    Route::post("/purchases", [PurchaseController::class, 'index']);
    Route::post("/purchases_by_provider", [PurchaseController::class, 'getProviderByPurchases']);
    Route::post("/purchase_deposit", [PurchaseController::class, 'saveDepositsHistory']);
    Route::post("/purchases_deposits_history", [PurchaseController::class, 'getDepositsHistory']);
    Route::delete("/purchase_deposit/{id}", [PurchaseController::class, 'destroyDeposit']);
    Route::post("/purchase", [PurchaseController::class, 'store']);
    Route::post("/purchases_excel", [PurchaseController::class, 'excelPurchases']);
    Route::post("/purchase_excel", [PurchaseController::class, 'excelPurchase']);
    Route::post('/purchase_pdf', [PurchaseController::class, 'pdfPurchase']);
    Route::post("/purchase/{id}", [PurchaseController::class, 'show']);
    Route::delete("/purchase/{id}", [PurchaseController::class, 'destroy']);
    Route::put("/purchase/{id}", [PurchaseController::class, 'update']);

    //? Detalles Compra
    Route::delete("/purchase_details/{id}", [PurchaseController::class, 'destroyDetails']);

//* Reportes

    Route::post("/reports", [ReportsController::class, 'getStatistics']);
    Route::get("/notifications", [ReportsController::class, 'getNotifications']);
