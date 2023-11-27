<?php

namespace CC_RezdyAPI\Rezdy\Requests;

/**
 * Creates and verifies the Product request
 *
 * @package Requests
 * @author Code Corners
 */
class Product extends BaseRequest implements RequestInterface
{

    public function __construct($params = '')
    {

        //Set the required properties of the object and the required type
        $this->requiredParams = [
            'description'                    => 'string|100-15000',
            'durationMinutes'                => 'integer',
            'name'                            => 'string',
            'productType'                    => 'enum.product-type',
            'shortDescription'                => 'string|15-240',
        ];

        //Set the optional properties of the object and the required type
        $this->optionalParams = [
            'additionalInformation'            =>    'string',
            'advertisedPrice'                =>    'numeric',
            'bookingMode'                    =>    'enum.booking-modes',
            'cancellationPolicyDays'         =>  'integer',
            'charter'                        =>    'boolean',
            'commissionIncludesExtras'        =>  'boolean',
            'confirmMode'                    =>  'enum.confirm-modes',
            'confirmModeMinParticipants'    =>     'integer',
            'currency'                        =>  'enum.currency-types',
            'internalCode'                    =>    'string',
            'generalTerms'                    =>     'string',
            'languages'                        =>     'array-of-string',
            'minimumNoticeMinutes'            =>    'integer',
            'maxCommissionNetRate'            =>    'numeric',
            'maxCommissionPercent'            =>    'numeric',
            'minimumNoticeMinutes'            =>    'integer',
            'productCode'                    =>    'string',
            'pickupId'                        =>    'integer',
            'qrCodeType'                    =>    'enum.qr-code-types',
            'quantityRequired'                =>    'boolean',
            'quantityRequiredMax'            =>    'integer',
            'quantityRequiredMin'            =>    'integer',
            'supplierAlias'                    =>  'string',
            'supplierId'                    =>  'integer',
            'supplierName'                    =>    'string',
            'tags'                            =>     'tag-or-array',
            'terms'                            =>    'string',
            'timezone'                        =>  'string',
            'unitLabel'                        =>    'string',
            'unitLabelPlural'                =>    'string',
            'waitListingEnabled'            =>    'boolean',
            'xeroAccount'                    =>    'string',
        ];

        // Sets the class mapping for single set items to the request 
        $this->setClassMap =     [
            'CC_RezdyAPI\Rezdy\Requests\Objects\LocationAddress'    => 'locationAddress',
        ];

        //Sets the class mapping for multiple item sets to the request 				
        $this->addClassMap  =     [
            'CC_RezdyAPI\Rezdy\Requests\Objects\Field'          => 'bookingFields',
            'CC_RezdyAPI\Rezdy\Requests\Extra'                  => 'extras',
            'CC_RezdyAPI\Rezdy\Requests\Objects\Image'          => 'images',
            'CC_RezdyAPI\Rezdy\Requests\Objects\PriceOption'    => 'priceOptions',
            'CC_RezdyAPI\Rezdy\Requests\Objects\SeoTag'         => 'productSeoTags',
            'CC_RezdyAPI\Rezdy\Requests\Objects\Tax'            => 'taxes',
            'CC_RezdyAPI\Rezdy\Requests\Objects\Video'          => 'videos',
        ];

        if (is_array($params)) {
            $this->buildFromArray($params);
        }

        // These are Required to Create the Product
        $this->bookingFields = array();
        $this->priceOptions = array();
    }

    public function isValid()
    {

        return $this->isValidRequest();
    }
}
