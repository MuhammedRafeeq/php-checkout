<?php
    
    class CheckOut 
    {

        protected $arrProducts = [];
        protected $arrSku = [];
        protected $arrCustomerItems = [];
        protected $arrFinalDeliveryItems = [];

        public function __construct($arrProducts) 
        {
            $this->arrProducts = $arrProducts;
            $this->arrSku = array_keys($arrProducts);
        }

        public function scanItems($strItems)
        {
            $arrItems = explode(',', $strItems);

            $this->arrCustomerItems = array_count_values($arrItems);
        }

        public function calculateTotal()
        {
            foreach($this->arrCustomerItems as $strSku => $intCount)
            {
                if(!in_array($strSku, $this->arrSku))
                    continue;
                
                switch ($strSku) {
                    case 'atv':
                        $this->applyAtvOffer($intCount);
                        break;

                    case 'ipd':
                        $this->applyIpdOffer($intCount);
                        break;

                    case 'mbp':
                        $this->applyMbpOffer($intCount);
                        break;

                    case 'vga':
                        $this->calculateVgaTotal($intCount);
                        break;
                    
                    default:
                        break;
                }

            }

            $this->printGrandTotal();
        }

        protected function applyAtvOffer($intCount)
        {
            $intTotalCount = $intCount;
            $dblPrice = $this->arrProducts['atv']['price'];

            if($intCount >= 3) {

                $intNonOfferCount = $intCount % 3;
                $intOfferCount = $intCount - $intNonOfferCount;
                $intTotalCount = ($intOfferCount * 2 / 3) + $intNonOfferCount;
            }

            $dblTotal = $intTotalCount * $dblPrice;

            $this->arrFinalDeliveryItems['atv'] = ['count' => $intCount, 'total' => $dblTotal];
        }

        protected function applyIpdOffer($intCount)
        {
            $dblPrice = $this->arrProducts['ipd']['price'];

            if($intCount > 4)
                $dblPrice = 499.99;

            $dblTotal = $intCount * $dblPrice;
            $this->arrFinalDeliveryItems['ipd'] = ['count' => $intCount, 'total' => $dblTotal];
        }

        protected function applyMbpOffer($intCount)
        {
            $dblPrice = $this->arrProducts['mbp']['price'];

            $dblTotal = $intCount * $dblPrice;

            $this->arrFinalDeliveryItems['mbp'] = ['count' => $intCount, 'total' => $dblTotal];
            $this->arrFinalDeliveryItems['vga'] = ['count' => $intCount, 'total' => 0];
        }

        protected function calculateVgaTotal($intCount)
        {
            $dblPrice = $this->arrProducts['vga']['price'];
            $dblPayableCount = $intCount;
            $intOfferCount = 0;

            if(isset($this->arrFinalDeliveryItems['vga'])) {
                $intOfferCount = $this->arrFinalDeliveryItems['vga']['count'];
                $dblPayableCount = 0;

                if($intCount > $this->arrFinalDeliveryItems['vga']['count'] )
                    $dblPayableCount = $intCount - $intOfferCount;
            }

                
            $dblTotal = $dblPayableCount * $dblPrice;
            $intTotalCount = $intOfferCount + $dblPayableCount;

            $this->arrFinalDeliveryItems['vga'] = ['count' => $intTotalCount, 'total' => $dblTotal];
        }

        protected function printGrandTotal()
        {
            echo '----------------------------------------------------' . PHP_EOL;
            printf('%-15.15s - Invoice -' . PHP_EOL, '', 'Invoice');
            echo '----------------------------------------------------' . PHP_EOL;
            printf('|%5.5s |%-30.30s |%-11.11s |' . PHP_EOL, 'SKU', 'Count', 'Total');
            echo '----------------------------------------------------' . PHP_EOL;

            $dblGrandTotal = 0;
            foreach($this->arrFinalDeliveryItems as $strSku => $arrItem) 
            {
                $dblGrandTotal += $arrItem['total'];
                $strTotal = number_format($arrItem['total'], 2);
                printf('|%5.5s |%-30.30s |$%-10.10s |' . PHP_EOL, $strSku, $arrItem['count'], $strTotal);
            }

            $strGrandTotal = number_format($dblGrandTotal, 2);
            echo '----------------------------------------------------' . PHP_EOL;
            printf('|%5.5s |%-30.30s |$%-10.10s |' . PHP_EOL, '', 'Grand Total', $strGrandTotal);
            echo '----------------------------------------------------' . PHP_EOL;
        }
    }



    $arrProducts = json_decode( file_get_contents('product.json'), true );
    $blnRun = true;

    while($blnRun) 
    {
        echo '----------------------------------------------------' . PHP_EOL;
        printf('|%5.5s |%-30.30s |%-11.11s |' . PHP_EOL, 'SKU', 'Name', 'Price');
        echo '----------------------------------------------------' . PHP_EOL;

        foreach($arrProducts as $strSku => $arrProduct) {

            printf('|%5.5s |%-30.30s |$%-10.10s |' . PHP_EOL, $strSku, $arrProduct['product'], $arrProduct['price']);
        }

        echo "Enter SKUs with comma separated: ";
        $handle = fopen ("php://stdin","r");
        $line = trim(fgets($handle));

        if($line) {
            
            $objCheckOut = new CheckOut($arrProducts);
            $objCheckOut->scanItems($line);
            $objCheckOut->calculateTotal($line);
        }

        echo "Enter y to continue: ";
        $handle = fopen ("php://stdin","r");
        
        if(trim(fgets($handle)) != 'y')
            exit();
    }

?>