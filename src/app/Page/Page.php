<?php

namespace CC_RezdyAPI\Page;

use CC_RezdyAPI\App;
use CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption;
use CC_RezdyAPI\Rezdy\Requests\Product;
use CC_RezdyAPI\Rezdy\Requests\ProductUpdate;
use CC_RezdyAPI\Rezdy\Requests\SessionBatchUpdate;
use CC_RezdyAPI\Rezdy\Util\Config;
use CC_RezdyAPI\RezdyAPI;
use CC_RezdyAPI\Rezdy\Requests\SessionCreate;
use CC_RezdyAPI\Rezdy\Requests\SessionSearch;
use CC_RezdyAPI\Rezdy\Requests\SessionUpdate;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as GuzzleClient;

class Page
{
    private $pageContext;

    public function __construct(App $pageContext)
    {
        $this->pageContext = $pageContext;
        //$this->setupActions();

        return $this;
    }
}
