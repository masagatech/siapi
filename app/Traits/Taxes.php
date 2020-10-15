<?php
namespace App\Traits;
trait Taxes{
    
        function taxcalculation($params,$sql){
            $params = json_decode($params,true);
            $probj = $params['probj'];
            file_put_contents('prdata.txt',print_r($sql,true));
            $sql = json_decode(json_encode($sql),true);
            
            
            file_put_contents('prodata.json',json_encode($sql,JSON_PRETTY_PRINT));
            $x = array();
            foreach($sql as $key=>$val){
                
                $pr = array('prid'=>$val['prodid'],'sellingprice'=>$val['sellingprice']);
                $vr = $val['vrid'];
                $x[$vr] = $pr;
                $q = '';
            }
            $x;
            $as = '';

            foreach ($x as $key=>&$val){
                foreach($probj as $k=>$v){
                    if($key == $v['variantid'] && $val['prid'] == $v['productid']){
                        $qty = $v['quantity'];
                        $val['qty'] = $qty;
                        $val['sellingamt'] = $qty * $val['sellingprice']; 
                    }
                }
            }
            $sql;
            unset($val);
            $x;
            $totaltaxamt = 0;
            $originalamt = 0;
            $totalamt = 0;
            $inctaxestosub = 0;
            $taxsplit = array();
            foreach($x as $key => $val){
                $arr = array();
                foreach($sql as $kk => $vv){
                    if($vv['prodid'] == $val['prid'] && $vv['vrid'] == $key ){
                        array_push($arr,$vv);
                    }
                }
                $arr;
                $vqty = $val['qty'];
                $prodfinaltaxes = $this->taxonprod($arr,$vqty);
                $ta = array(
                    "prodid"=>$arr[0]['prodid'],
                    "vrid"=>$arr[0]['vrid'],
                    "sellingprice"=>$arr[0]['sellingprice']*$vqty,
                    "taxes"=>$prodfinaltaxes
                );
                $originalamt += $arr[0]['sellingprice']*$vqty;
                $totaltaxamt += $prodfinaltaxes['totaltaxamt'];
                $inctaxestosub += $prodfinaltaxes['inctaxtosub'];
                array_push($taxsplit,$ta);
            }
            $totaltaxamttoshow=$totaltaxamt-$inctaxestosub;
            $totalamt = $totaltaxamttoshow + $originalamt;
            return array(
                "originalamt"=> $originalamt,
                "totalamt"=>$totalamt,
                "totaltaxamt"=>$totaltaxamt,
                "totalinctaxes"=>$inctaxestosub,
                "totaltaxamttoshow"=>$totaltaxamttoshow,
                "taxsplit"=>$taxsplit,
                "sqldata"=>$sql            
            );
        }

        function taxonprod($arr,$qty){
            $prod = $arr;
            $isinclusive = false;
            $isexclusive = false;
            $inclusivesper = array();
            $inclusivesamt = array();
            $suminctaxvalamt = 0;
            $suminctaxvalper = 0;
            $exclusivesper = array();
            $exclusivesamt = array();
            $sumextaxvalamt = 0;
            $sumextaxvalper = 0;
            $taxableamt = 0;
            $totaltaxamt = 0;
            foreach($arr as $key=>$val){
                $taxableamt = $val['sellingprice']*$qty;
                if($val['inclusive'] == 'true'){
                    $isinclusive = true;
                    if($val['taxcat'] == 'Amount'){
                        array_push($inclusivesamt,$val);
                        $suminctaxvalamt += $val['taxvalue']*$qty;
                    }else{
                        array_push($inclusivesper,$val);
                        $suminctaxvalper += $val['taxvalue'];
                    }
                }else{
                    $isexclusive = true;
                    if($val['taxcat'] == 'Amount'){
                        array_push($exclusivesamt,$val);
                        $sumextaxvalamt += $val['taxvalue']*$qty;
                    }else{
                        array_push($exclusivesper,$val);
                        $sumextaxvalper += $val['taxvalue'];
                    }
                }
            }
            $totaltaxincper = 0;
            $totaltaxincamt = 0;
            if($isinclusive){
                if(sizeof($inclusivesamt) > 0){
                    $taxableamt = $taxableamt - $suminctaxvalamt;
                }
                if(sizeof($inclusivesper)>0){
                    $taxableamt = ($taxableamt * 100) / (100 + $suminctaxvalper);
                }
                $totaltaxamt = ($suminctaxvalper / 100) * $taxableamt;
                $totaltaxincper = $totaltaxamt;
                $totaltaxincamt = $suminctaxvalamt;
                $totaltaxamt += $suminctaxvalamt;
            }

            
            
            if($isexclusive){
                $totaltaxamt += ($sumextaxvalper/100) * $taxableamt;
                $totaltaxamt += $sumextaxvalamt;
            }
            $totalprodamt = $taxableamt+$totaltaxamt;
            $final = array(
                "taxableamt"=>$taxableamt,
                "totaltaxamt"=>$totaltaxamt,
                "suminctaxvalamt"=> $suminctaxvalamt ,
                "suminctaxvalper"=>$suminctaxvalper ,
                "sumextaxvalamt"=>$sumextaxvalamt ,
                "sumextaxvalper"=>$sumextaxvalper ,
                "totalprodamt"=>$totalprodamt,
                "totaltaxincper"=>$totaltaxincper,
                "totaltaxincamt"=>$totaltaxincamt,
                "inctaxtosub"=>$totaltaxincamt+$totaltaxincper
            );
            return $final;
        } 
    }
?>