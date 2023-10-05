<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExcelMakeController extends Controller
{
    //
	public function excel()
	{
        Log::info('ExcelMakeController excel START');

		$exceloutfilepath = storage_path().'app/public/invoice/xls/folder0002/20230201_from_to_請求書.xlsx';
        $exceltmpfilepath = storage_path().'app/public/invoice/xls/tmp/tmp_invoice.xlsx';
		$excel_file = storage_path($exceltmpfilepath);
		Excel::load($excel_file, function($reader) {
			// 1番目のシートを選択
			$reader->sheet(0, function($sheet) {
				// セルM!に現在の日付を書き込み
				$sheet->cell('M1', function($cell) {
				    $cell->setValue( now()->format('Y/m/d') );
				});		    
			});		    
		// })->export('xlsx');
		})->export($exceloutfilepath);

        Log::info('ExcelMakeController excel END');

    }

    public function makePdf()
    {
        // Log::info('ExcelMakeController makePdf START');

        // // もとになるExcelを読み込み
        // // $excel_file = storage_path('app/excel/template/template.xlsx');
		// $excel_file = storage_path().'app/public/invoice/xls/tmp/tmp_invoice.xlsx';
        // $reader = new XlsxReader();
        // $spreadsheet = $reader->load($excel_file);

        // // 編集するシート名を指定
        // $worksheet = $spreadsheet->getSheetByName('invoice');

        // // セルに指定した値挿入
        // $worksheet->setCellValue('M1', now()->format('Y/m/d'));

        // // Excel出力
		// $file_name = '使わない';
        // $writer = new XlsxWriter($spreadsheet);
        // // $export_excel_path = storage_path('app/excel/export/' . $file_name . '.xlsx');
		// $export_excel_path = storage_path().'app/public/invoice/xls/folder0002/20230201_from_to_請求書.xlsx';

        // $writer->save($export_excel_path);

        // Log::info('ExcelMakeController makePdf END');

        // // Pdf出力
        // if (file_exists($export_excel_path)) {
        //     $export_pdf_path = storage_path('app/pdf/export');
        //     $cmd = 'export HOME=/tmp; libreoffice --headless --convert-to pdf --outdir ' . $export_pdf_path . ' ' . $export_excel_path;
        //     exec($cmd);
        // }
    }

}
