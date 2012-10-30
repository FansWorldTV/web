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
 * Live Stream service lets you manage live stream channels
 *  
 * @package Kaltura
 * @subpackage Client
 */
class LiveStreamService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * Adds new live stream entry.
	 * 	 The entry will be queued for provision.
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\LiveStreamAdminEntry
	 */
	function add(\Kaltura\Client\Type\LiveStreamAdminEntry $liveStreamEntry, $sourceType = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "liveStreamEntry", $liveStreamEntry->toParams());
		$this->client->addParam($kparams, "sourceType", $sourceType);
		$this->client->queueServiceActionCall("livestream", "add", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\LiveStreamAdminEntry");
		return $resultObject;
	}

	/**
	 * Get live stream entry by ID.
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\LiveStreamEntry
	 */
	function get($entryId, $version = -1)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "version", $version);
		$this->client->queueServiceActionCall("livestream", "get", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\LiveStreamEntry");
		return $resultObject;
	}

	/**
	 * Update live stream entry. Only the properties that were set will be updated.
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\LiveStreamAdminEntry
	 */
	function update($entryId, \Kaltura\Client\Type\LiveStreamAdminEntry $liveStreamEntry)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "liveStreamEntry", $liveStreamEntry->toParams());
		$this->client->queueServiceActionCall("livestream", "update", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\LiveStreamAdminEntry");
		return $resultObject;
	}

	/**
	 * Delete a live stream entry.
	 * 	 
	 * 
	 * @return
	 */
	function delete($entryId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->queueServiceActionCall("livestream", "delete", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * List live stream entries by filter with paging support.
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\LiveStreamListResponse
	 */
	function listAction(\Kaltura\Client\Type\LiveStreamEntryFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("livestream", "list", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\LiveStreamListResponse");
		return $resultObject;
	}

	/**
	 * Update live stream entry thumbnail using a raw jpeg file
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\LiveStreamEntry
	 */
	function updateOfflineThumbnailJpeg($entryId, $fileData)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$kfiles = array();
		$this->client->addParam($kfiles, "fileData", $fileData);
		$this->client->queueServiceActionCall("livestream", "updateOfflineThumbnailJpeg", $kparams, $kfiles);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\LiveStreamEntry");
		return $resultObject;
	}

	/**
	 * Update entry thumbnail using url
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\LiveStreamEntry
	 */
	function updateOfflineThumbnailFromUrl($entryId, $url)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "url", $url);
		$this->client->queueServiceActionCall("livestream", "updateOfflineThumbnailFromUrl", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\LiveStreamEntry");
		return $resultObject;
	}
}
