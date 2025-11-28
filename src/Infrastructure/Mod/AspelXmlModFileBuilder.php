<?php
declare(strict_types=1);

namespace Prosa\Orders\Infrastructure\Mod;

use Prosa\Orders\Domain\Mod\ModFile;
use Prosa\Orders\Domain\Mod\ModFileBuilder;
use Prosa\Orders\Domain\Order\Order;
use Prosa\Orders\Domain\Order\OrderLine;

class AspelXmlModFileBuilder implements ModFileBuilder
{
    public function buildFromOrder(Order $order): ModFile
    {
        $timestamp = date('YmdHis');
        $fileName = 'PED_' . trim($order->client()->id()->value()) . '_' . $order->store() . '_' . $timestamp . '.mod';

        // Estructura básica de DATAPACKET para Aspel SAE.
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<DATAPACKET Version="2.0">';
        $xml .= '<METADATA>';
        $xml .= '<FIELDS>';
        $xml .= '<FIELD attrname="TIP_DOC" fieldtype="string" WIDTH="1" />';
        $xml .= '<FIELD attrname="CVE_DOC" fieldtype="string" WIDTH="20" />';
        $xml .= '<FIELD attrname="CVE_CLPV" fieldtype="string" WIDTH="10" />';
        $xml .= '<FIELD attrname="STATUS" fieldtype="string" WIDTH="1" />';
        $xml .= '<FIELD attrname="CVE_VEND" fieldtype="string" WIDTH="5" />';
        $xml .= '<FIELD attrname="FECHA_DOC" fieldtype="string" WIDTH="10" />';
        $xml .= '<FIELD attrname="SU_REFER" fieldtype="string" WIDTH="20" />';
        $xml .= '<FIELD attrname="NUM_ALMA" fieldtype="i4" />';
        $xml .= '<FIELD attrname="TIP_CAM" fieldtype="r8" />';
        $xml .= '<FIELD attrname="RFC" fieldtype="string" WIDTH="15" />';
        $xml .= '<FIELD attrname="CVE_ART" fieldtype="string" WIDTH="20" />';
        $xml .= '<FIELD attrname="CANT" fieldtype="r8" />';
        $xml .= '<FIELD attrname="PXS" fieldtype="r8" />';
        $xml .= '<FIELD attrname="PREC" fieldtype="r8" />';
        $xml .= '<FIELD attrname="COST" fieldtype="r8" />';
        $xml .= '<FIELD attrname="IMPORTE" fieldtype="r8" />';
        $xml .= '<FIELD attrname="NUM_PAR" fieldtype="i4" />';
        $xml .= '<FIELD attrname="UNI_VENTA" fieldtype="string" WIDTH="6" />';
        $xml .= '</FIELDS>';
        $xml .= '<PARAMS CHANGE_LOG="FALSE" />';
        $xml .= '</METADATA>';
        $xml .= '<ROWDATA>';

        $numPar = 1;
        foreach ($order->lines() as $line) {
            /** @var OrderLine $line */
            $importe = $line->quantity() * $line->price();
            $xml .= '<ROW';
            $xml .= ' TIP_DOC="P"';
            $xml .= ' CVE_DOC="' . htmlspecialchars('AUTO', ENT_QUOTES, 'UTF-8') . '"';
            $xml .= ' CVE_CLPV="' . htmlspecialchars($order->client()->id()->padded(), ENT_QUOTES, 'UTF-8') . '"';
            $xml .= ' STATUS="E"';
            $xml .= ' CVE_VEND="1"';
            $xml .= ' FECHA_DOC="' . date('Y-m-d') . '"';
            $xml .= ' SU_REFER="' . htmlspecialchars($order->store(), ENT_QUOTES, 'UTF-8') . '"';
            $xml .= ' NUM_ALMA="1"';
            $xml .= ' TIP_CAM="1"';
            $xml .= ' RFC="XAXX010101000"';
            $xml .= ' CVE_ART="' . htmlspecialchars($line->productCode(), ENT_QUOTES, 'UTF-8') . '"';
            $xml .= ' CANT="' . $line->quantity() . '"';
            $xml .= ' PXS="1"';
            $xml .= ' PREC="' . $line->price() . '"';
            $xml .= ' COST="' . $line->price() . '"';
            $xml .= ' IMPORTE="' . $importe . '"';
            $xml .= ' NUM_PAR="' . $numPar . '"';
            $xml .= ' UNI_VENTA="PIEZA"';
            $xml .= ' />';
            $numPar++;
        }

        $xml .= '</ROWDATA>';
        $xml .= '</DATAPACKET>';

        return new ModFile($fileName, $xml);
    }
}
