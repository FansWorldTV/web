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
namespace Kaltura\Client\Plugin\FileSync\Service;

/**
 * System user service
 *  
 * @package Kaltura
 * @subpackage Client
 */
class FileSyncService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * List file syce objects by filter and pager
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\FileSync\Type\FileSyncListResponse
	 */
	function listAction(\Kaltura\Client\Plugin\FileSync\Type\FileSyncFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("filesync_filesync", "list", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\FileSync\\Type\\FileSyncListResponse");
		return $resultObject;
	}

	/**
	 * 
	 * @return \Kaltura\Client\Plugin\FileSync\Type\FileSync
	 */
	function sync($fileSyncId, $fileData)
	{
		$kparams = array();
		$this->client->addParam($kparams, "fileSyncId", $fileSyncId);
		$kfiles = array();
		$this->client->addParam($kfiles, "fileData", $fileData);
		$this->client->queueServiceActionCall("filesync_filesync", "sync", $kparams, $kfiles);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\FileSync\\Type\\FileSync");
		return $resultObject;
	}
}
