<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mv\Megaventory\Cron;

class ApplyPendingUpdates
{

    protected $_megaventoryService;

    public function __construct(\Mv\Megaventory\Model\Services\MegaventoryService $megaventoryService)
    {
        $this->_megaventoryService = $megaventoryService;
    }

    public function execute()
    {
        $this->_megaventoryService->applyPendingUpdates();
    }
}
