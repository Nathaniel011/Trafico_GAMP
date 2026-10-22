<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $table = 'certificates';
    public $products = ['HAMBURGUESAS', 'RELLENOS', 'SALTEÑAS Y TUCUMANAS', 'VENTA DE DULCES', 'REFRIGERIOS', 'COPIA DE LLAVES', 'CASETA DE SEGURIDAD', 'REFACCION DE ZAPATOS', 'OTROS (DESCRIBIR)'];
    public function getAnotherTypeAttribute(){
        $type = $this->tipo;
        if(in_array($type, ['ANAQUEL', 'KIOSKO', 'CASETA'])){
            return '';
        }
        return $type;
    }
    public function getRefOtherProductAttribute(){
        $product = $this->ref_product;
        if(in_array($product, $this->products)){
            return '';
        }
        return $product;
    }
}
