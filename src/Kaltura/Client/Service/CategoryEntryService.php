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
namespace Kaltura\Client\Service;

/**
 * Add & Manage CategoryEntry - assign entry to category
 *  
 * @package Kaltura
 * @subpackage Client
 */
class CategoryEntryService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * Add new CategoryEntry
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\CategoryEntry
	 */
	function add(\Kaltura\Client\Type\CategoryEntry $categoryEntry)
	{
		$kparams = array();
		$this->client->addParam($kparams, "categoryEntry", $categoryEntry->toParams());
		$this->client->queueServiceActionCall("categoryentry", "add", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\CategoryEntry");
		return $resultObject;
	}

	/**
	 * Delete CategoryEntry
	 * 	 
	 * 
	 * @return
	 */
	function delete($entryId, $categoryId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "categoryId", $categoryId);
		$this->client->queueServiceActionCall("categoryentry", "delete", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * List all categoryEntry
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\CategoryEntryListResponse
	 */
	function listAction(\Kaltura\Client\Type\CategoryEntryFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("categoryentry", "list", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\CategoryEntryListResponse");
		return $resultObject;
	}

	/**
	 * Index CategoryEntry by Id
	 * 	 
	 * 
	 * @return int
	 */
	function index($entryId, $categoryId, $shouldUpdate = true)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "categoryId", $categoryId);
		$this->client->addParam($kparams, "shouldUpdate", $shouldUpdate);
		$this->client->queueServiceActionCall("categoryentry", "index", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (int)$resultObject;
		return $resultObject;
	}

	/**
	 * activate CategoryEntry when it is pending moderation
	 * 	 
	 * 
	 * @return
	 */
	function activate($entryId, $categoryId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "categoryId", $categoryId);
		$this->client->queueServiceActionCall("categoryentry", "activate", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * activate CategoryEntry when it is pending moderation
	 * 	 
	 * 
	 * @return
	 */
	function reject($entryId, $categoryId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "categoryId", $categoryId);
		$this->client->queueServiceActionCall("categoryentry", "reject", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}
}
