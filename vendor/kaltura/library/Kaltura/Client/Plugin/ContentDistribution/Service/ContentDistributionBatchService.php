<?php
// ===================================================================================================
//                           _  __     _ _
//                          | |/ /__ _| | |_ _  _ _ _ __ _
//                          | ' </ _` | |  _| || | '_/ _` |
//                          |_|\_\__,_|_|\__|\_,_|_| \__,_|
//
// This file is part of the Kaltura Collaborative Media Suite which allows users
// to do with audio, video, and animation what Wiki platfroms allow them to do with
// text.
//
// Copyright (C) 2006-2011  Kaltura Inc.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// @ignore
// ===================================================================================================


/**
 * @namespace
 */
namespace Kaltura\Client\Plugin\ContentDistribution\Service;

/**
 * @package Kaltura
 * @subpackage Client
 */
class ContentDistributionBatchService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * updates entry distribution sun status in the search engine
	 * 	 
	 * 
	 * @return
	 */
	function updateSunStatus()
	{
		$kparams = array();
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "updateSunStatus", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * creates all required jobs according to entry distribution dirty flags 
	 * 	 
	 * 
	 * @return
	 */
	function createRequiredJobs()
	{
		$kparams = array();
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "createRequiredJobs", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * returns absolute valid url for asset file
	 * 	 
	 * 
	 * @return string
	 */
	function getAssetUrl($assetId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "assetId", $assetId);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "getAssetUrl", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (string)$resultObject;
		return $resultObject;
	}

	/**
	 * batch addBulkUploadResultAction action adds KalturaBulkUploadResult to the DB
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\BulkUploadResult
	 */
	function addBulkUploadResult(\Kaltura\Client\Type\BulkUploadResult $bulkUploadResult, array $pluginDataArray = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "bulkUploadResult", $bulkUploadResult->toParams());
		if ($pluginDataArray !== null)
			foreach($pluginDataArray as $index => $obj)
			{
				$this->client->addParam($kparams, "pluginDataArray:$index", $obj->toParams());
			}
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "addBulkUploadResult", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\BulkUploadResult");
		return $resultObject;
	}

	/**
	 * batch getBulkUploadLastResultAction action returns the last result of the bulk upload
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\BulkUploadResult
	 */
	function getBulkUploadLastResult($bulkUploadJobId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "bulkUploadJobId", $bulkUploadJobId);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "getBulkUploadLastResult", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\BulkUploadResult");
		return $resultObject;
	}

	/**
	 * Returns total created entries count
	 * 	 
	 * 
	 * @return int
	 */
	function countBulkUploadEntries($bulkUploadJobId, $bulkUploadObjectType = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "bulkUploadJobId", $bulkUploadJobId);
		$this->client->addParam($kparams, "bulkUploadObjectType", $bulkUploadObjectType);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "countBulkUploadEntries", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (int)$resultObject;
		return $resultObject;
	}

	/**
	 * batch updateBulkUploadResults action adds KalturaBulkUploadResult to the DB
	 * 	 
	 * 
	 * @return int
	 */
	function updateBulkUploadResults($bulkUploadJobId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "bulkUploadJobId", $bulkUploadJobId);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "updateBulkUploadResults", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (int)$resultObject;
		return $resultObject;
	}

	/**
	 * batch updateExclusiveConvertCollectionJobAction action updates a BatchJob of type CONVERT_PROFILE that was claimed using the getExclusiveConvertJobs
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\BatchJob
	 */
	function updateExclusiveConvertCollectionJob($id, \Kaltura\Client\Type\ExclusiveLockKey $lockKey, \Kaltura\Client\Type\BatchJob $job, array $flavorsData = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "job", $job->toParams());
		if ($flavorsData !== null)
			foreach($flavorsData as $index => $obj)
			{
				$this->client->addParam($kparams, "flavorsData:$index", $obj->toParams());
			}
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "updateExclusiveConvertCollectionJob", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\BatchJob");
		return $resultObject;
	}

	/**
	 * batch updateExclusiveConvertJobSubType action updates the sub type for a BatchJob of type CONVERT that was claimed using the getExclusiveConvertJobs
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\BatchJob
	 */
	function updateExclusiveConvertJobSubType($id, \Kaltura\Client\Type\ExclusiveLockKey $lockKey, $subType)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "subType", $subType);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "updateExclusiveConvertJobSubType", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\BatchJob");
		return $resultObject;
	}

	/**
	 * batch addMediaInfoAction action saves a media info object
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\MediaInfo
	 */
	function addMediaInfo(\Kaltura\Client\Type\MediaInfo $mediaInfo)
	{
		$kparams = array();
		$this->client->addParam($kparams, "mediaInfo", $mediaInfo->toParams());
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "addMediaInfo", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\MediaInfo");
		return $resultObject;
	}

	/**
	 * batch getExclusiveNotificationJob action allows to get a BatchJob of type NOTIFICATION 
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\BatchGetExclusiveNotificationJobsResponse
	 */
	function getExclusiveNotificationJobs(\Kaltura\Client\Type\ExclusiveLockKey $lockKey, $maxExecutionTime, $numberOfJobs, \Kaltura\Client\Type\BatchJobFilter $filter = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "maxExecutionTime", $maxExecutionTime);
		$this->client->addParam($kparams, "numberOfJobs", $numberOfJobs);
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "getExclusiveNotificationJobs", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\BatchGetExclusiveNotificationJobsResponse");
		return $resultObject;
	}

	/**
	 * batch resetJobExecutionAttempts action resets the execution attempts of the job 
	 * 	 
	 * 
	 * @return
	 */
	function resetJobExecutionAttempts($id, \Kaltura\Client\Type\ExclusiveLockKey $lockKey, $jobType)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "jobType", $jobType);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "resetJobExecutionAttempts", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * batch freeExclusiveJobAction action allows to get a generic BatchJob 
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\FreeJobResponse
	 */
	function freeExclusiveJob($id, \Kaltura\Client\Type\ExclusiveLockKey $lockKey, $jobType, $resetExecutionAttempts = false)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "jobType", $jobType);
		$this->client->addParam($kparams, "resetExecutionAttempts", $resetExecutionAttempts);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "freeExclusiveJob", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\FreeJobResponse");
		return $resultObject;
	}

	/**
	 * batch getQueueSize action get the queue size for job type 
	 * 	 
	 * 
	 * @return int
	 */
	function getQueueSize(\Kaltura\Client\Type\WorkerQueueFilter $workerQueueFilter)
	{
		$kparams = array();
		$this->client->addParam($kparams, "workerQueueFilter", $workerQueueFilter->toParams());
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "getQueueSize", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (int)$resultObject;
		return $resultObject;
	}

	/**
	 * batch getExclusiveJobsAction action allows to get a BatchJob 
	 * 	 
	 * 
	 * @return array
	 */
	function getExclusiveJobs(\Kaltura\Client\Type\ExclusiveLockKey $lockKey, $maxExecutionTime, $numberOfJobs, \Kaltura\Client\Type\BatchJobFilter $filter = null, $jobType = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "maxExecutionTime", $maxExecutionTime);
		$this->client->addParam($kparams, "numberOfJobs", $numberOfJobs);
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		$this->client->addParam($kparams, "jobType", $jobType);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "getExclusiveJobs", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		if(!$resultObject)
			$resultObject = array();
		$this->client->validateObjectType($resultObject, "array");
		return $resultObject;
	}

	/**
	 * batch getExclusiveAlmostDone action allows to get a BatchJob that wait for remote closure 
	 * 	 
	 * 
	 * @return array
	 */
	function getExclusiveAlmostDone(\Kaltura\Client\Type\ExclusiveLockKey $lockKey, $maxExecutionTime, $numberOfJobs, \Kaltura\Client\Type\BatchJobFilter $filter = null, $jobType = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "maxExecutionTime", $maxExecutionTime);
		$this->client->addParam($kparams, "numberOfJobs", $numberOfJobs);
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		$this->client->addParam($kparams, "jobType", $jobType);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "getExclusiveAlmostDone", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		if(!$resultObject)
			$resultObject = array();
		$this->client->validateObjectType($resultObject, "array");
		return $resultObject;
	}

	/**
	 * batch updateExclusiveJobAction action updates a BatchJob of extended type that was claimed using the getExclusiveJobs
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\BatchJob
	 */
	function updateExclusiveJob($id, \Kaltura\Client\Type\ExclusiveLockKey $lockKey, \Kaltura\Client\Type\BatchJob $job)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "lockKey", $lockKey->toParams());
		$this->client->addParam($kparams, "job", $job->toParams());
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "updateExclusiveJob", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\BatchJob");
		return $resultObject;
	}

	/**
	 * batch cleanExclusiveJobs action mark as fatal error all expired jobs
	 * 	 
	 * 	 
	 * 
	 * @return int
	 */
	function cleanExclusiveJobs()
	{
		$kparams = array();
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "cleanExclusiveJobs", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (int)$resultObject;
		return $resultObject;
	}

	/**
	 * Add the data to the flavor asset conversion log, creates it if doesn't exists
	 * 	 
	 * 
	 * @return
	 */
	function logConversion($flavorAssetId, $data)
	{
		$kparams = array();
		$this->client->addParam($kparams, "flavorAssetId", $flavorAssetId);
		$this->client->addParam($kparams, "data", $data);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "logConversion", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * batch checkFileExists action check if the file exists
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\FileExistsResponse
	 */
	function checkFileExists($localPath, $size)
	{
		$kparams = array();
		$this->client->addParam($kparams, "localPath", $localPath);
		$this->client->addParam($kparams, "size", $size);
		$this->client->queueServiceActionCall("contentdistribution_contentdistributionbatch", "checkFileExists", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\FileExistsResponse");
		return $resultObject;
	}
}
