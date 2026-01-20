<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ItemType;
use App\Models\PropertyType;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = Product::with(['brand', 'category', 'subCategory', 'itemType', 'propertyType'])
                ->where('action', '0')
                ->latest();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('brand_name', function($row){
                    return $row->brand ? $row->brand->name : '-';
                })
                ->addColumn('category_name', function($row){
                    return $row->category ? $row->category->name : '-';
                })
                ->addColumn('item_type_name', function($row){
                    return $row->itemType ? $row->itemType->name : '-';
                })
                ->make(true);
        }

        // Load masters for dropdowns
        $brands = Brand::where('action', '0')->get();
        $categories = Category::where('action', '0')->get();
        $itemTypes = ItemType::where('action', '0')->get();
        $propertyTypes = PropertyType::where('action', '0')->get();
        
        // We'll load subcategories via AJAX or just all for now to keep simple if small list
        // Better to load all and filter JS side or use API
        $subCategories = SubCategory::where('action', '0')->get();

        return view('masters.products.index', compact('brands', 'categories', 'itemTypes', 'propertyTypes', 'subCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'item_code' => 'required|unique:products,item_code',
            'name' => 'required',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'item_type_id' => 'nullable|exists:item_types,id',
        ]);

        Product::create(array_merge($request->all(), ['action' => '0']));

        return response()->json(['success' => 'Product added']);
    }

    public function edit(Product $product)
    {
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'item_code' => 'required|unique:products,item_code,' . $id,
            'name' => 'required',
        ]);

        Product::where('id', $id)->update($request->except(['_token', '_method', 'id']));

        return response()->json(['success' => 'Updated']);
    }

    public function destroy($id)
    {
        Product::where('id', $id)->update(['action' => '1']);
        return response()->json(['success' => 'Product deactivated']);
    }

    public function bulkDelete(Request $request)
    {
        if (!$request->has('ids') || !is_array($request->ids)) return response()->json(['error' => 'No selection'], 422);

        Product::whereIn('id', $request->ids)->update(['action' => '1']);

        return response()->json(['success' => 'Selected products deactivated']);
    }


    public function uploadPreview(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls',
            'head_row' => 'required|integer|min:1'
        ]);

        $file = $request->file('excel_file');
        $headRow = $request->input('head_row');
        $filename = 'import_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('temp', $filename, 'local');

        // Store file info in session for the next step
        session(['import_path' => $filePath, 'import_head' => $headRow]);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($filePath));
        $headers = $spreadsheet->getActiveSheet()->toArray()[$headRow - 1];

        // All fields matching user requirements
        $fields = [
            'item_code' => 'Item Code / ID*',
            'brand_id' => 'Brand Name*',
            'model_name' => 'Model Name',
            'property_type_id' => 'Property Type*',
            'length' => 'Length',
            'pieces_per_packet' => 'Pieces / Packet',
            'section_weight' => 'Section Weight',
            'name' => 'Item Name*',
            'item_type_id' => 'Item Type*',
            'category_id' => 'Main Category*',
            'sub_category_id' => 'Sub Category*',
            'tax_rate' => 'Tax Rate',
            'uom' => 'UOM'
        ];

        $html = '';
        foreach ($fields as $dbColumn => $label) {
            $html .= '<div class="space-y-1">
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest">'.$label.'</label>
                <select name="mapping['.$dbColumn.']" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold">
                    <option value="">-- Ignore Field --</option>';
            foreach ($headers as $key => $headerName) {
                $html .= '<option value="'.$key.'">'.$headerName.'</option>';
            }
            $html .= '</select></div>';
        }

        return response()->json(['mappingFields' => $html]);
    }

    public function importView()
    {
        return view('masters.products.import');
    }

    public function importProducts(Request $request)
    {
        $path = session('import_path');
        $headRow = session('import_head');
        $mapping = $request->input('mapping');

        if (!Storage::disk('local')->exists($path)) {
            return response()->json(['error' => 'File expired or not found. Please upload again.'], 404);
        }

        $sheet = IOFactory::load(Storage::disk('local')->path($path))->getActiveSheet();
        $rows = array_slice($sheet->toArray(), $headRow);
        
        $success = 0; $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                // 1. Extract mapped values
                $data = [];
                foreach ($mapping as $dbCol => $excelIndex) {
                    if ($excelIndex !== null && isset($row[$excelIndex])) {
                        $data[$dbCol] = trim($row[$excelIndex]);
                    }
                }

                // Skip simple empty rows or missing Item Code
                if (empty($data['item_code']) || empty($data['name'])) continue;

                // 2. Handle Auto-Creation of Masters
                
                // Brand
                if (!empty($data['brand_id'])) {
                    $brand = Brand::firstOrCreate(
                        ['name' => $data['brand_id']],
                        ['action' => '0']
                    );
                    $data['brand_id'] = $brand->id;
                } else {
                    $data['brand_id'] = null;
                }

                // Property Type
                if (!empty($data['property_type_id'])) {
                    $prop = PropertyType::firstOrCreate(
                        ['name' => $data['property_type_id']],
                        ['action' => '0']
                    );
                    $data['property_type_id'] = $prop->id;
                } else {
                    $data['property_type_id'] = null;
                }

                // Item Type
                if (!empty($data['item_type_id'])) {
                    $type = ItemType::firstOrCreate(
                        ['name' => $data['item_type_id']],
                        ['action' => '0']
                    );
                    $data['item_type_id'] = $type->id;
                } else {
                    $data['item_type_id'] = null;
                }

                // Category (Main Category)
                $categoryId = null;
                if (!empty($data['category_id'])) {
                    $cat = Category::firstOrCreate(
                        ['name' => $data['category_id']],
                        ['action' => '0']
                    );
                    $categoryId = $cat->id;
                    $data['category_id'] = $categoryId;
                } else {
                    $data['category_id'] = null;
                }

                // Sub Category (Dependent on Category)
                // Only create Sub Category if we have a Main Category
                if (!empty($data['sub_category_id']) && $categoryId) {
                    $subCat = SubCategory::firstOrCreate(
                        [
                            'name' => $data['sub_category_id'],
                            'category_id' => $categoryId
                        ],
                        ['action' => '0']
                    );
                    $data['sub_category_id'] = $subCat->id;
                } else {
                    $data['sub_category_id'] = null;
                }

                // 3. Create or Update Product
                $product = Product::updateOrCreate(
                    ['item_code' => $data['item_code']],
                    array_merge($data, ['action' => '0'])
                );

                $product->wasRecentlyCreated ? $success++ : $updated++;
            }

            DB::commit();
            Storage::disk('local')->delete($path);
            
            return response()->json(['successSummary' => "Successfully Imported: Created $success, Updated $updated"]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Import Failed: ' . $e->getMessage()], 500);
        }
    }

    public function export()
    {
        $products = Product::with(['brand', 'category', 'subCategory', 'itemType', 'propertyType'])
            ->where('action', '0')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = [
            'A1' => 'Item Code',
            'B1' => 'Name',
            'C1' => 'Brand',
            'D1' => 'Model',
            'E1' => 'Property Type',
            'F1' => 'Length',
            'G1' => 'Pieces / Packet',
            'H1' => 'Section Weight',
            'I1' => 'Item Type',
            'J1' => 'Category',
            'K1' => 'Sub Category',
            'L1' => 'Tax Rate',
            'M1' => 'UOM'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $product->item_code);
            $sheet->setCellValue('B' . $row, $product->name);
            $sheet->setCellValue('C' . $row, $product->brand ? $product->brand->name : '');
            $sheet->setCellValue('D' . $row, $product->model_name);
            $sheet->setCellValue('E' . $row, $product->propertyType ? $product->propertyType->name : '');
            $sheet->setCellValue('F' . $row, $product->length);
            $sheet->setCellValue('G' . $row, $product->pieces_per_packet);
            $sheet->setCellValue('H' . $row, $product->section_weight);
            $sheet->setCellValue('I' . $row, $product->itemType ? $product->itemType->name : '');
            $sheet->setCellValue('J' . $row, $product->category ? $product->category->name : '');
            $sheet->setCellValue('K' . $row, $product->subCategory ? $product->subCategory->name : '');
            $sheet->setCellValue('L' . $row, $product->tax_rate);
            $sheet->setCellValue('M' . $row, $product->uom);
            $row++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
