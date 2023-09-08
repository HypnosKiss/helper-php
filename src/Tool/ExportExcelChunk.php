<?php

namespace Sweeper\HelperPhp\Tool;

/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/8/27 23:42
 * @Path \Sweeper\HelperPhp\Tool\ExportExcelChunk
 */
class ExportExcelChunk
{

    public static function sendHeader($filename): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header("Content-Disposition: inline; filename=\"$filename.xls\"");
        header('Content-Transfer-Encoding: binary');
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        ob_flush();
        flush();
    }

    /**
     * 通过PHP组合字符串方式获取xml头部字符串
     * @param array $headers 表头
     * @return string $xml
     */
    public static function getXmlHeadString(array $headers): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">                  
        <Styles>
            <Style ss:ID="sDT"><NumberFormat ss:Format="Short Date"/></Style>
        </Styles>
        <Worksheet ss:Name="Sheet1">
        <Table x:FullColumns="1" x:FullRows="1" ss:StyleID="s16" ss:DefaultColumnWidth="54" ss:DefaultRowHeight="18.75">';
        $xml .= '<Row>';
        foreach ($headers as $value) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($value) . '</Data></Cell>';
        }
        $xml .= '</Row>';

        return $xml;
    }

    /**
     * 通过PHP组合字符串方式获取xmlBody字符串
     * @param array $data 数据
     * @return string $xml
     */
    public static function getXmlBodyString(array $data): string
    {
        $xml  = '<Row>';
        $data = array_values($data);
        foreach ($data as $val) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($val) . '</Data></Cell>';
        }
        $xml .= '</Row>';

        return $xml;
    }

    /**
     * 通过PHP组合字符串方式获取xmlFoot字符串
     * @return string $xml
     */
    public static function getXmlFootString(): string
    {
        return '</Table></Worksheet></Workbook>';
    }

    /**
     * 输出 通过PHP组合字符串方式获取xml头部字符串
     * @param $headers
     */
    public static function outputXmlHeadString($headers): void
    {
        echo static::getXmlHeadString($headers);
    }

    /**
     * 输出 通过PHP组合字符串方式获取xmlBody字符串
     * @param $data
     */
    public static function outputXmlBodyString($data): void
    {
        echo static::getXmlBodyString($data);
    }

    /**
     * 输出 通过PHP组合字符串方式获取xmlFoot字符串
     */
    public static function outputXmlFootString(): void
    {
        echo static::getXmlFootString();
    }

}