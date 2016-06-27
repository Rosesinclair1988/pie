<?php

/*
 * @copyright  Copyright (c) 2011 by  ESS-UA.
 */

class Ess_M2ePro_Model_Processing_Cron
{
    const MAX_PROCESSING_IDS_PER_REQUEST = 100;

    // ########################################

    public function process()
    {
        $this->clearOldProcessingRequests();
        $this->processProcessingRequests();
    }

    // ########################################

    private function clearOldProcessingRequests()
    {
        $currentDateTime = Mage::helper('M2ePro')->getCurrentGmtDate(true);
        $maxLifeTimeInterval = Ess_M2ePro_Model_Processing_Request::MAX_LIFE_TIME_INTERVAL;

        $minCreateTimeStamp = $currentDateTime - $maxLifeTimeInterval;
        $minCreateDateTime = Mage::helper('M2ePro')->getDate($minCreateTimeStamp);

        /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
        $collection = Mage::getModel('M2ePro/Processing_Request')->getCollection();
        $collection->getSelect()->where('create_date < \''.$minCreateDateTime.'\'');

        $processingRequests = $collection->getItems();
        $this->executeFailedProcessingRequests($processingRequests);
    }

    private function processProcessingRequests()
    {
        $components = Mage::helper('M2ePro/Component')->getComponents();

        foreach ($components as $component) {

            /** @var $collection Mage_Core_Model_Mysql4_Collection_Abstract */
            $collection = Mage::getModel('M2ePro/Processing_Request')->getCollection();
            $collection->addFieldToFilter('component',$component);
            $processingRequests = $collection->getItems();

            $processingSingleObjects = array();
            $processingPartialObjects = array();

            foreach ($processingRequests as $processingRequest) {
                /** @var $processingRequest Ess_M2ePro_Model_Processing_Request */
                if ($processingRequest->isPerformTypeSingle()) {
                    $processingSingleObjects[] = $processingRequest;
                } else {
                    $processingPartialObjects[] = $processingRequest;
                }
            }

            $processingIds = array();
            $processingObjects = array();

            foreach ($processingSingleObjects as $processingRequest) {
                /** @var $processingRequest Ess_M2ePro_Model_Processing_Request */
                $processingIds[] = $processingRequest->getProcessingHash();
                if (!isset($processingObjects[$processingRequest->getProcessingHash()])) {
                    $processingObjects[$processingRequest->getProcessingHash()] = array();
                }
                $processingObjects[$processingRequest->getProcessingHash()][] = $processingRequest;
            }

            $this->processSingleProcessingRequests($component,array_unique($processingIds),$processingObjects);

            $processingIds = array();
            $processingObjects = array();

            foreach ($processingPartialObjects as $processingRequest) {
                /** @var $processingRequest Ess_M2ePro_Model_Processing_Request */
                $processingIds[] = $processingRequest->getProcessingHash();
                if (!isset($processingObjects[$processingRequest->getProcessingHash()])) {
                    $processingObjects[$processingRequest->getProcessingHash()] = array();
                }
                $processingObjects[$processingRequest->getProcessingHash()][] = $processingRequest;
            }

            $this->processPartialProcessingRequests($component,array_unique($processingIds),$processingObjects);
        }
    }

    // ########################################

    private function processSingleProcessingRequests($component, array $processingIds, array $processingObjects)
    {
        $processingIdsParts = array_chunk($processingIds,self::MAX_PROCESSING_IDS_PER_REQUEST);

        foreach ($processingIdsParts as $processingIds) {

            // send parts to the server
            $dispatcherObject = Mage::getModel('M2ePro/Connector_Server_'.ucfirst($component).'_Dispatcher');
            $results = $dispatcherObject->processVirtualAbstract('processing','get','results',
                                                                  array('processing_ids'=>$processingIds),
                                                                 'results', NULL, NULL);

            if (empty($results)) {
                continue;
            }

            // process results
            foreach ($processingIds as $processingId) {

                if (!isset($results[$processingId]) || !isset($results[$processingId]['status']) ||
                    $results[$processingId]['status'] == Ess_M2ePro_Model_Processing_Request::STATUS_NOT_FOUND) {
                    $processingRequests = $processingObjects[$processingId];
                    $this->executeFailedProcessingRequests($processingRequests);
                    continue;
                }

                if ($results[$processingId]['status'] != Ess_M2ePro_Model_Processing_Request::STATUS_COMPLETE) {
                    continue;
                }

                !isset($results[$processingId]['data']) && $results[$processingId]['data'] = array();
                !isset($results[$processingId]['messages']) && $results[$processingId]['messages'] = array();

                $processingRequests = $processingObjects[$processingId];

                foreach ($processingRequests as $processingRequest) {

                    /** @var $processingRequest Ess_M2ePro_Model_Processing_Request */
                    $modelName = $processingRequest->getResponserModel();
                    $className = Mage::getConfig()->getModelClassName($modelName);

                    /** @var $responserObject Ess_M2ePro_Model_Connector_Server_Responser */
                    $responserObject = new $className($processingRequest);
                    $responserObject->processCompleted((array)$results[$processingId]['data'],
                                                       (array)$results[$processingId]['messages']);
                }
            }
        }
    }

    private function processPartialProcessingRequests($component, array $processingIds, array $processingObjects)
    {
        foreach ($processingIds as $processingId) {
            $this->processPartialNextPart($component,$processingId,$processingObjects[$processingId],1);
        }
    }

    // ########################################

    private function processPartialNextPart($component, $processingId, array $processingRequests, $necessaryPart)
    {
        $params = array(
            'processing_id' => $processingId,
            'necessary_parts' => array(
                $processingId => (int)$necessaryPart
            )
        );

        // send parts to the server
        $dispatcherObject = Mage::getModel('M2ePro/Connector_Server_'.ucfirst($component).'_Dispatcher');
        $results = $dispatcherObject->processVirtualAbstract('processing','get','results',
                                                              $params, 'results', NULL, NULL);

        if (empty($results)) {
            return;
        }

        if (!isset($results[$processingId]) || !isset($results[$processingId]['status']) ||
            $results[$processingId]['status'] == Ess_M2ePro_Model_Processing_Request::STATUS_NOT_FOUND) {
            $this->executeFailedProcessingRequests($processingRequests);
            return;
        }

        if ($results[$processingId]['status'] != Ess_M2ePro_Model_Processing_Request::STATUS_COMPLETE) {
            return;
        }

        !isset($results[$processingId]['data']) && $results[$processingId]['data'] = array();
        !isset($results[$processingId]['messages']) && $results[$processingId]['messages'] = array();

        $nextPart = NULL;
        if (isset($results[$processingId]['next_part']) &&
            (int)$results[$processingId]['next_part'] >= 2) {
            $nextPart = (int)$results[$processingId]['next_part'];
        }

        $nextProcessingRequests = array();
        foreach ($processingRequests as $processingRequest) {

            /** @var $processingRequest Ess_M2ePro_Model_Processing_Request */
            $modelName = $processingRequest->getResponserModel();
            $className = Mage::getConfig()->getModelClassName($modelName);

            /** @var $responserObject Ess_M2ePro_Model_Connector_Server_Responser */
            $responserObject = new $className($processingRequest);
            $tempResult = $responserObject->process((array)$results[$processingId]['data'],
                                                    (array)$results[$processingId]['messages']);

            if ($tempResult) {
                if (is_null($nextPart)) {
                    $responserObject->completeSuccessfulProcessing();
                } else {
                    $nextProcessingRequests[] = $processingRequest;
                }
            }
        }

        if (!is_null($nextPart) && count($nextProcessingRequests) > 0) {
            unset($results);
            $this->processPartialNextPart($component,$processingId,$nextProcessingRequests,$nextPart);
        }
    }

    //----------------------------------------

    private function executeFailedProcessingRequests(array $processingRequests)
    {
        foreach ($processingRequests as $processingRequest) {

            if (!($processingRequest instanceof Ess_M2ePro_Model_Processing_Request)) {
                continue;
            }

            /** @var $processingRequest Ess_M2ePro_Model_Processing_Request */
            $processingRequest->executeAsFailed('Request wait timeout exceeded.');
        }
    }

    // ########################################
}
