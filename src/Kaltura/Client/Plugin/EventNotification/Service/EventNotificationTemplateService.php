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
namespace Kaltura\Client\Plugin\EventNotification\Service;

/**
 * Event notification template service lets you create and manage event notification templates
 *  
 * @package Kaltura
 * @subpackage Client
 */
class EventNotificationTemplateService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * Allows you to add a new event notification template object
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate
	 */
	function add(\Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate $eventNotificationTemplate)
	{
		$kparams = array();
		$this->client->addParam($kparams, "eventNotificationTemplate", $eventNotificationTemplate->toParams());
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "add", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplate");
		return $resultObject;
	}

	/**
	 * Allows you to clone exiting event notification template object and create a new one with similar configuration
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate
	 */
	function cloneAction($id, \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate $eventNotificationTemplate)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "eventNotificationTemplate", $eventNotificationTemplate->toParams());
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "clone", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplate");
		return $resultObject;
	}

	/**
	 * Retrieve an event notification template object by id
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate
	 */
	function get($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "get", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplate");
		return $resultObject;
	}

	/**
	 * Update an existing event notification template object
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate
	 */
	function update($id, \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate $eventNotificationTemplate)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "eventNotificationTemplate", $eventNotificationTemplate->toParams());
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "update", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplate");
		return $resultObject;
	}

	/**
	 * Update event notification template status by id
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate
	 */
	function updateStatus($id, $status)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "status", $status);
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "updateStatus", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplate");
		return $resultObject;
	}

	/**
	 * Delete an event notification template object
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplate
	 */
	function delete($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "delete", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplate");
		return $resultObject;
	}

	/**
	 * list event notification template objects
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplateListResponse
	 */
	function listAction(\Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplateFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "list", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplateListResponse");
		return $resultObject;
	}

	/**
	 * 
	 * @return \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationTemplateListResponse
	 */
	function listByPartner(\Kaltura\Client\Type\PartnerFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "listByPartner", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\EventNotification\\Type\\EventNotificationTemplateListResponse");
		return $resultObject;
	}

	/**
	 * Dispatch event notification object by id
	 * 	 
	 * 
	 * @return int
	 */
	function dispatch($id, \Kaltura\Client\Plugin\EventNotification\Type\EventNotificationDispatchJobData $data)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "data", $data->toParams());
		$this->client->queueServiceActionCall("eventnotification_eventnotificationtemplate", "dispatch", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (int)$resultObject;
		return $resultObject;
	}
}
