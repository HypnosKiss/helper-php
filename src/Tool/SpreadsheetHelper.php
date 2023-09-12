<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/3/30 9:18
 */

namespace Sweeper\HelperPhp\Tool;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

!defined('WWW_PATH') && define('WWW_PATH', str_replace('＼＼', '/', dirname(__DIR__, 4) . '/'));  // 定义站点目录
!defined('APP_PATH') && define('APP_PATH', $_SERVER['DOCUMENT_ROOT'] ?: WWW_PATH);              // 定义应用目录

/**
 * 电子表格助手
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:24
 * @Path \Sweeper\HelperPhp\Tool\SpreadsheetHelper
 * @package 'phpoffice/phpspreadsheet': '^1.19.0',
 */
class SpreadsheetHelper
{

    /** @var string Redis 缓存适配器 */
    public const CACHE_ADAPTER_REDIS = 'redis';

    /** @var string 文件缓存适配器 */
    public const CACHE_ADAPTER_FILE = 'file';

    /**
     * 同步头部与数据长度
     * @param $header
     * @param $row
     */
    private static function dataSyncLen(&$header, &$row): void
    {
        $head_len = count($header);
        $row_len  = count($row);
        if ($head_len > $row_len) {
            $row = array_pad($row, $head_len, '');
        } elseif ($head_len < $row_len) {
            for ($i = 0; $i < ($row_len - $head_len); $i++) {
                $header[] = 'Row' . ($head_len + $i);
            }
        }
    }

    /**
     * 读取CSV格式文件
     * @param       $file
     * @param array $config
     * @return array
     */
    public static function readCsvFile($file, array $config = []): array
    {
        $config = array_merge([
            'start_offset'     => 1,        //数据开始行（如果首行为下标，start_offset必须大于0）
            'first_row_as_key' => true,     //是否首行作为数据下标返回（如果是，start_offset必须大于0）
            'fields'           => [],       //指定返数据下标（按顺序对应）
            'delimiter'        => ',',      //分隔符
            'from_encoding'    => 'gbk',    //来源编码
            'to_encoding'      => 'utf-8',  //目标编码
        ], $config);

        $data   = [];
        $header = [];
        static::readCsvFileChunk($file, static function($row, $row_idx) use (&$data, &$header, $config) {
            if ($row_idx == 0) {
                if ($config['first_row_as_key']) {
                    $header = $row;

                    return;
                }
                if ($config['fields']) {
                    $header = $config['fields'];
                }
            }
            if ($row_idx >= $config['start_offset']) {
                static::dataSyncLen($header, $row);
                $data[] = $config['first_row_as_key'] ? array_combine($header, $row) : $row;
            }
        }, $config);

        return $data;
    }

    /**
     * 分块读取CSV文件
     * @param string   $file        CSV文件名
     * @param callable $row_handler 行处理器，传参为：(array $row, int row_index)
     * @param array    $config      选项
     * @return array
     */
    public static function readCsvFileChunk(string $file, callable $row_handler, array $config = []): array
    {
        $config = array_merge([
            'delimiter'     => ',',      //分隔符
            'from_encoding' => 'gbk',    //来源编码
            'to_encoding'   => 'utf-8',  //目标编码
        ], $config);

        $data    = [];
        $row_idx = 0;
        $fp      = fopen($file, 'r');
        while (($row = fgetcsv($fp, 0, $config['delimiter'])) !== false) {
            $row = array_map(function($str) use ($config) {
                $str = trim($str);

                return $str ? (iconv($config['from_encoding'], $config['to_encoding'], $str) ?: $str) : $str;
            }, $row);
            $row_handler($row, $row_idx);
            $row_idx++;
        }

        return $data;
    }

    /**
     * 导出 Excel
     * User: Sweeper
     * Time: 2023/3/30 9:33
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @param string                                $filename
     * @param string                                $writerType
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportExcelBySpreadsheet(Spreadsheet $spreadsheet, string $filename, string $writerType = 'Xlsx'): void
    {
        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename={$filename}");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');              // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate');               // HTTP/1.1
        header('Pragma: public');                                      // HTTP/1.0
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($spreadsheet, $writerType);
        $writer->save('php://output');
    }

    /**
     * 数据导出
     * User: Sweeper
     * Time: 2023/3/29 14:45
     * @doc https://cloud.tencent.com/developer/article/2131242
     * @doc https://cloud.tencent.com/developer/article/1737072
     * @doc https://blog.csdn.net/ice_mocha/article/details/116460057
     * @doc https://www.mrcdh.cn/pages/abd24c/#%E5%AE%89%E8%A3%85%E4%BE%9D%E8%B5%96
     * @doc https://phpspreadsheet.readthedocs.io/en/latest/topics/migration-from-PHPExcel/
     * @param array                                  $headers      头组数
     * @param array                                  $dataList     数据多维数组
     * @param array                                  $params       其他参数 ['title' => 'test', 'output' => false, 'onlyOutput' => false, 'directory' => null, 'filePath' => null, 'maxRow' => 10000,
     *                                                             'namespace' => 'excel', 'lifetime' => 36000]
     * @param string                                 $adapterType  缓存适配器类型
     * @param \Psr\Cache\CacheItemPoolInterface|null $cacheAdapter 缓存适配器
     * @return mixed|string|\think\response\Download|void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportDataToExcel(array $headers, array $dataList, array $params = [], string $adapterType = self::CACHE_ADAPTER_FILE, CacheItemPoolInterface $cacheAdapter = null)
    {
        $dataList   = array_chunk($dataList, 10000);
        $headers    = array_values($headers);
        $title      = $params['title'] ?? 'example';                                  // 文件标题
        $output     = $params['output'] ?? false;                                     // 输出文件到浏览器
        $onlyOutput = $params['onlyOutput'] ?? false;                                 // 只输出不保存文件
        $directory  = $params['directory'] ?? APP_PATH . '/runtime/cache/data';       // 缓存和文件的输出目录
        $filePath   = $params['filePath'] ?? "{$directory}/{$title}.xlsx";            // 包含全路径的文件名
        $fileName   = basename($filePath);                                            // 返回路径中的文件名部分
        $filename   = $fileName ?: "{$title}.xlsx";                                   // 输出到浏览器的文件名
        $maxRow     = $params['maxRow'] ?? 10000;                                     // 每个表格的最大行数，默认10000
        $namespace  = $params['namespace'] ?? 'excel';                                // 缓存命名空间
        $lifetime   = $params['lifetime'] ?? 36000;                                   // 缓存生命周期
        $sheetTitle = strlen($title) > 30 ? 'sheetTitle' : $title;                    // 表格标题
        $pathInfo   = pathinfo($filePath, PATHINFO_DIRNAME);                          // 文件路径
        if (!is_dir($pathInfo) && !mkdir($pathInfo, 0777, true) && !is_dir($pathInfo)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathInfo));
        }

        //调用示例 在实例化 Spreadsheet 对象前设置
        switch ($adapterType) {
            case static::CACHE_ADAPTER_REDIS:
                $adapter = new RedisAdapter(RedisClient::instance()->connection(), $namespace, $lifetime);
                break;
            case static::CACHE_ADAPTER_FILE:
                $adapter = new FilesystemAdapter($namespace, $lifetime, $directory);
                break;
            default:
                $adapter = null;
                break;
        }
        if ($cacheAdapter = $cacheAdapter ?? $adapter) {
            $cache = new Psr16Cache($cacheAdapter);
            Settings::setCache($cache);
        }

        if ($filePath && is_file($filePath)) {// 存在文件，追加
            $spreadsheet = IOFactory::load($filePath);
            $sheetCount  = $spreadsheet->getSheetCount();
            for ($i = 0; $i < $sheetCount; $i++) {
                $highestRow = $spreadsheet->setActiveSheetIndex($i)->getHighestRow();// 设置活动表格, 获取最大行数
                if ($highestRow < $maxRow) {
                    break;
                }
            }
            $highestRow        = $spreadsheet->getActiveSheet()->getHighestRow();                                           // 取得总行数
            $highestColumn     = $spreadsheet->getActiveSheet()->getHighestColumn();                                        // 取得列数 字母abc...
            $activeSheetIndex  = $spreadsheet->getActiveSheetIndex();                                                       // 获取当前活动表格
            $currentCoordinate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1, $highestRow + 1)->getCoordinate();// 从最大行数后面开始写数据
            foreach ($dataList as $sheetIndex => $_dataList) {
                $currentSheetIndex = $sheetIndex + $activeSheetIndex;
                $spreadsheet->setActiveSheetIndex($currentSheetIndex)->setTitle("{$sheetTitle}-{$currentSheetIndex}")->freezePane('B2')->fromArray($_dataList, null, $currentCoordinate, true);
                static::setLineStyle($spreadsheet);
            }
        } else {
            // 创建一个处理对象实例,实例化 Spreadsheet 对象
            $spreadsheet = new Spreadsheet();
            foreach ($dataList as $sheetIndex => $_dataList) {
                array_unshift($_dataList, $headers);
                $spreadsheet->setActiveSheetIndex($sheetIndex)->setTitle("{$sheetTitle}-{$sheetIndex}")->freezePane('B2')->fromArray($_dataList, null, 'A1', true);
                static::setLineStyle($spreadsheet);
            }
        }

        $spreadsheet->getProperties()
                    ->setCompany('tenflyer')
                    ->setManager('tenflyer')
                    ->setCreator('Sweeper')
                    ->setLastModifiedBy('Sweeper')
                    ->setTitle($sheetTitle)
                    ->setSubject($sheetTitle)
                    ->setDescription($sheetTitle)
                    ->setKeywords($sheetTitle)
                    ->setCategory('Product public pool export');

        $writer = new Xlsx($spreadsheet);
        // $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->setPreCalculateFormulas(false);                                      //禁用公式预计算
        $writer->setOffice2003Compatibility(true);
        if ($onlyOutput) {
            static::exportExcelBySpreadsheet($spreadsheet, $filename);
        } else {
            $writer->save($filePath);
            // 写入完成后，释放内存
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($output) {
                return download($filePath, $filename, false, 3600, false);
            }

            return $filePath;
        }
    }

    /**
     * 样式设置
     * User: Sweeper
     * Time: 2023/4/3 9:23
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet
     * @return void
     */
    public static function setLineStyle(Spreadsheet $spreadsheet): void
    {
        $highestRow           = $spreadsheet->getActiveSheet()->getHighestRow();   // 取得总行数
        $highestColumn        = $spreadsheet->getActiveSheet()->getHighestColumn();// 取得列数 字母abc...
        $cellCoordinateColumn = "A1:{$highestColumn}1";
        $cellCoordinateRow    = "A2:A{$highestRow}";
        $styleArray           = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_NONE,
                    'color'       => ['argb' => Color::COLOR_BLACK],
                ],
            ],
            'fill'    => [
                'fillType' => Fill::FILL_SOLID,
                'color'    => ['argb' => Color::COLOR_YELLOW],
            ],
            'font'    => [
                'bold'   => false,
                'italic' => false,
                'name'   => 'Arial',
                'size'   => 12,
                'color'  => ['argb' => Color::COLOR_BLACK],
            ],
        ];
        $spreadsheet->getActiveSheet()->getStyle($cellCoordinateColumn)->applyFromArray($styleArray);
        $spreadsheet->getActiveSheet()->getStyle($cellCoordinateRow)->getFill()->setFillType(Fill::FILL_NONE)->getStartColor()->setARGB(Color::COLOR_WHITE);
        $spreadsheet->getActiveSheet()->getStyle($cellCoordinateRow)->getFont()->setBold(false)->setName('')->setSize(10)->getColor()->setRGB();
        $spreadsheet->getActiveSheet()->setSelectedCell('A2');
    }

    /**
     * excel 导入
     * @param string $path
     * @param array  $title_arr
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public static function excelImport(string $path, array $title_arr = []): array
    {
        $objPHPExcel = IOFactory::load($path);
        $nameArr     = $objPHPExcel->getsheetnames();
        //获取表的数量
        $sheetCount = $objPHPExcel->getSheetCount();
        //循环读取每一张表
        $sheet = $objPHPExcel->getActiveSheet();
        # 获取有数据的总行数
        $highestRow = $sheet->getHighestRow();
        //获取总列数
        $rowLetter = $sheet->getHighestColumn();
        # 获取总列数数字化
        $highestColumnIndex = Coordinate::columnIndexFromString($rowLetter);
        //第一行字段名
        $title = [];
        //数据内容
        $excelData = [];
        //表头验证结果
        $titleValidate = [];

        //数组标号
        $i = 0;
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $value = trim($sheet->getCellByColumnAndRow($column, $row)->getFormattedValue());
                if ($row === 1) {
                    $title[] = trim($value);
                } else {
                    $excelData[$i][$title[$column - 1]] = trim($value);
                }
            }
            $i++;
        }

        // 验证表头是否存在
        if (!empty($title_arr) && !empty($title)) {
            foreach ($title_arr as $val) {
                if (!in_array($val, $title, true)) {
                    $titleValidate[] = '表头列不存在: ' . $val . '！';
                }
            }
            if ($titleValidate) {
                $validate_result              = [];
                $validate_result['error']     = 1;
                $validate_result['error_msg'] = $titleValidate;

                return $validate_result;
            }
        }

        return $excelData;
    }

    /**
     * 导出excel表格
     * User: Sweeper
     * Time: 2023/9/11 17:33
     * @param array  $data     导出数据
     * @param array  $title    表格第一行标题
     * @param string $fileName 导出的文件名
     * @param string $fileType 导出的文件名后缀
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function excelExport(array $data, array $title, string $fileName, string $fileType = 'xls'): void
    {
        //Excel文件类型校验
        $type = ['Excel2007', 'Xlsx', 'Excel5', 'Xls', 'Csv'];
        if (!in_array($fileType, $type)) {
            throw new \InvalidArgumentException("不支持文件类型{$fileType}");
        }

        if (empty($data)) {
            throw new \InvalidArgumentException('导出数据为空');
        }

        //设置文件头返回头
        if ($fileType === 'Excel2007' || $fileType === 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . urlencode($fileName) . '.xlsx"');
            header('Access-Control-Expose-Headers: Content-Disposition');
        } else { //Excel5
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . urlencode($fileName) . '.xls"');
            header('Access-Control-Expose-Headers: Content-Disposition');
        }
        header('Cache-Control: max-age=0');

        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->getActiveSheet()->setTitle('sheet1');
        foreach ($title as $k => $v) {
            $worksheet->setCellValueByColumnAndRow($k + 1, 1, $v);
        }
        //从第二行开始
        $row = 2;

        //遍历数据
        foreach ($data as $val) {
            $column = 1;
            foreach ($val as $value) {
                // 设置单元格格式
                if (is_numeric($value)) {
                    $worksheet->getStyleByColumnAndRow($column, $row)
                              ->getNumberFormat()
                              ->setFormatCode(NumberFormat::FORMAT_TEXT);
                }
                $worksheet->setCellValueByColumnAndRow($column, $row, $value);
                $column++;
            }
            $row++;
        }
        ob_end_clean();
        //2.输出到浏览器
        $writer = IOFactory::createWriter($spreadsheet, $fileType); //按照指定格式生成Excel文件
        $writer->save('php://output');
        /* 释放内存 */
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        try {
            ob_end_flush();
        } catch (\Exception $e) {

        }
    }

    /**
     * 导入Excel表取出需要的内容
     * User: Sweeper
     * Time: 2023/9/11 18:05
     * @param string $excelPath excel表路径
     * @param array  $param     每列对应数据键名及标题 ['A' => ['key' => 'A',title => '标题名称']] 标题名为空则不验证
     * @param int    $startRow  内容开始的行
     * @param bool   $yield     [['A' => 'content']];
     * @return array|\Generator
     * @throws \PhpOffice\PhpSpreadsheet\Calculation\Exception
     */
    public static function importExcel(string $excelPath, array $param, int $startRow = 2, bool $yield = true)
    {
        $excelObj = IOFactory::load($excelPath);
        if (!$excelObj) {
            throw new \InvalidArgumentException('加载Excel表失败，请检查Excel内容');
        }
        $excelWorkSheet = $excelObj->getActiveSheet();
        $rowCount       = $excelWorkSheet->getHighestRow();
        if ($rowCount <= 0) {
            throw new \InvalidArgumentException('Excel表内容为空。');
        }
        // 验证标题
        foreach ($param as $column => $content) {
            $cell = $column . ($startRow - 1);
            $item = $excelWorkSheet->getCell($cell)->getCalculatedValue();
            if (!empty($content['title']) && $item !== $content['title']) {
                throw new \InvalidArgumentException("请检查模板标题是否正确($cell => {$content['title']})。");
            }
        }
        $excelData = [];
        for ($row = $startRow; $row <= $rowCount; $row++) {
            $rowData = [];
            foreach ($param as $column => $content) {
                $item                     = $excelWorkSheet->getCell($column . $row)->getCalculatedValue();
                $rowData[$content['key']] = $item;
            }
            if (!implode('', $rowData)) {
                continue;//删除空行
            }
            if ($yield) {
                yield $rowData;
            } else {
                $excelData[] = $rowData;
            }
        }
        if (!$yield) {
            return $excelData;
        }
    }

}