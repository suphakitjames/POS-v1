<?php

namespace App\Helpers;

/**
 * PromptPay QR Code Generator
 * โดยใช้การสร้าง Payload ตามมาตรฐาน EMVCo
 */
class PromptPayQR
{
    /**
     * สร้าง QR Code Payload สำหรับ PromptPay
     * 
     * @param string $mobileOrTaxId เบอร์มือถือ (0812345678) หรือเลขประจำตัวผู้เสียภาษี (13 หลัก)
     * @param float $amount จำนวนเงิน
     * @return string Payload สำหรับสร้าง QR Code
     */
    public static function generate($mobileOrTaxId, $amount = null)
    {
        // ลบช่องว่างและขีด
        $id = preg_replace('/[^0-9]/', '', $mobileOrTaxId);

        // ตรวจสอบประเภท ID
        if (strlen($id) == 10) {
            // เบอร์มือถือ: เติม 66 ข้างหน้า และตัด 0 ออก
            $id = '66' . substr($id, 1);
            $aidType = '01'; // Mobile Number
        } elseif (strlen($id) == 13) {
            // Tax ID
            $aidType = '02'; // Tax ID
        } else {
            throw new \Exception('รูปแบบเบอร์มือถือหรือเลขประจำตัวผู้เสียภาษีไม่ถูกต้อง');
        }

        // สร้าง Payload
        $payload = '';

        // 00: Payload Format Indicator
        $payload .= self::buildTLV('00', '01');

        // 01: Point of Initiation Method (Static QR = 11, Dynamic QR = 12)
        $payload .= self::buildTLV('01', $amount ? '12' : '11');

        // 29: Merchant Account (PromptPay)
        $merchantAccount = self::buildTLV('00', 'A000000677010111'); // PromptPay AID
        $merchantAccount .= self::buildTLV($aidType, $id);
        $payload .= self::buildTLV('29', $merchantAccount);

        // 53: Currency (764 = THB)
        $payload .= self::buildTLV('53', '764');

        // 54: Amount (ถ้ามี)
        if ($amount && $amount > 0) {
            $payload .= self::buildTLV('54', number_format($amount, 2, '.', ''));
        }

        // 58: Country Code
        $payload .= self::buildTLV('58', 'TH');

        // 63: CRC (จะเติมทีหลัง)
        $payload .= '6304'; // Tag 63 + Length 04

        // คำนวณ CRC16
        $crc = self::crc16($payload);
        $payload .= $crc;

        return $payload;
    }

    /**
     * สร้าง TLV (Tag-Length-Value) Format
     */
    private static function buildTLV($tag, $value)
    {
        $length = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $tag . $length . $value;
    }

    /**
     * คำนวณ CRC16 CCITT
     */
    private static function crc16($data)
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
