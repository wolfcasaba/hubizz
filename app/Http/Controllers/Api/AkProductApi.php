<?php

namespace App\Http\Controllers\Api;

use Exception;
use ZipArchive;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AkProductApi
{
    /**
     * Akbilisim API.
     *
     * @var AkApi
     */
    private $api;

    private $updates = [];

    private $updates_file = 'updates.json';

    public function __construct()
    {
        $this->api = new AkApi;

        $this->initUpdates();
    }


    /**
     * Register a product.
     *
     * @param int $item_id
     * @param string $code
     *
     * @return array|bool
     */
    public function registerPurchase($item_id, $code)
    {
        // validate the code
        $response = false;

        $args = array(
            'item_id'       => trim($item_id),
            'purchase_code' => trim($code),
        );

        //if (empty($args['purchase_code']) || !$this->validatePurchaseCodeFormat($args['purchase_code'])) {
        if (empty($args['purchase_code'])) {
            return ['status' => 'error', 'message' => 'Please add valid purchase code!'];
        }

        if (empty($args['item_id'])) {
            return ['status' => 'error', 'message' => 'Item id required!'];
        }

        $response = $this->api->handle('register-purchase', $args);

        if ('success' == $response['status'] && isset($response['data']['access_code'])) {
            $this->api->registerAccessCode($args['item_id'], $response['data']['access_code']);
        }

        if (isset($response['data']['package'])) {
            $this->fetchFiles($response['data']['package']);
        }

        return $response;
    }

    /**
     * Register a product.
     *
     * @param int $item_id
     * @param string $code
     *
     * @return array|bool
     */
    public function checkPurchase($code, $item_id)
    {
        // validate the code
        $response = false;

        if (!empty($item_id)) {
            $args = array(
                'item_id'     => trim($item_id),
                'access_code' => trim($code),
            );

            $response = $this->api->handle('check-purchase', $args);
        }

        return $response;
    }

    /**
     * Check a product.
     *
     * @param int $item_id
     *
     * @return array|bool
     */
    public function checkItemPurchase($item_id)
    {
        $response = false;

        if (!empty($item_id)) {
            $args = array(
                'item_id'     => $item_id,
                'access_code' => $this->api->getAccessCode($item_id),
            );

            $response = $this->api->handle('check-purchase', $args);
        }

        return $response;
    }

    /**
     * Check updates.
     *
     * @return array|bool
     */
    public function checkUpdates()
    {
        $item_id = config('buzzy.item_id');

        $args = array(
            'item_id'     => $item_id,
            'access_code' => $this->api->getAccessCode($item_id),
        );

        $response = $this->api->handle('check-update', $args);

        if (!$response || $response['status'] === 'error') {
            return false;
        }

        return $response['data'];
    }

    /**
     * Init updates.
     *
     * HUBIZZ MODIFICATION: Disable automatic update checks
     * This prevents daily API calls to Akbilisim servers
     *
     * @return bool
     */
    private function initUpdates()
    {
        // HUBIZZ: Disabled automatic update checking
        // Original code would connect to Akbilisim API daily
        return true;
    }

    /**
     * Get updates.
     *
     * HUBIZZ MODIFICATION: Return false to disable update notifications
     *
     * @return bool
     */
    public function getUpdates()
    {
        // HUBIZZ: No update checking - return false to disable notifications
        return false;
    }

    /**
     * Retrieve product theme updates.
     *
     * HUBIZZ MODIFICATION: Return empty array to disable theme update checks
     *
     * @return array
     */
    public function getThemes()
    {
        // HUBIZZ: No theme update checking - return empty array
        // Original code would fetch themes from Akbilisim API
        return [];
    }

    /**
     * Retrieve product plugin updates.
     *
     * HUBIZZ MODIFICATION: Return empty array to disable plugin update checks
     *
     * @return array
     */
    public function getPlugins()
    {
        // HUBIZZ: No plugin update checking - return empty array
        // Original code would fetch plugins from Akbilisim API
        return [];
    }

    /**
     * Download a product.
     *
     * @param int $item_id
     * @param string $item_version
     *
     * @return bool
     */
    public function downloadUpdates($item_id, $item_version)
    {
        $args = array(
            'item_id'   => $item_id,
            'item_version' => $item_version,
            'access_code' => $this->api->getAccessCode($item_id),
        );

        try {
            return $this->fetchFiles($this->api->getApiUrl('get-update-download-url', $args));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Fetch download.
     *
     * @param string $zurl
     *
     * @return bool
     */
    public function fetchFiles($zurl)
    {
        try {
            $zip_path = base_path('tmp.zip');
            file_put_contents($zip_path, fopen($zurl, 'r'));
            if (!file_exists($zip_path)) {
                return false;
            }
            $zip = new ZipArchive;
            if (!$zip) {
                return false;
            }
            $zip->open("$zip_path");
            if ($zip->locateName("/vendor/") !== false) {
                rename(base_path('vendor'), base_path('vendorold' . time()));
            }

            $zip->extractTo(base_path());
            $zip->close();
            @unlink($zip_path);

            if (file_exists(resource_path('cleanup.php'))) {
                include_once(resource_path('cleanup.php'));
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Check if license key format is valid.
     *
     * license key is version 4 UUID, that have form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
     * where x is any hexadecimal digit and y is one of 8, 9, A, or B.
     *
     * @param string $purchase_code
     *
     * @return boolean
     */
    public function validatePurchaseCodeFormat($purchase_code)
    {
        $purchase_code = trim($purchase_code);
        $pattern       = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';

        return (bool) preg_match($pattern, $purchase_code);
    }
}
