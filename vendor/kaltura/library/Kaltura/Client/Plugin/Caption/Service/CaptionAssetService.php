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
namespace Kaltura\Client\Plugin\Caption\Service;

/**
 * Retrieve information and invoke actions on caption Asset
 *  
 * @package Kaltura
 * @subpackage Client
 */
class CaptionAssetService extends \Kaltura\Client\ServiceBase
{
	function __construct(\Kaltura\Client\Client $client = null)
	{
		parent::__construct($client);
	}

	/**
	 * Add caption asset
	 *      
	 * 
	 * @return \Kaltura\Client\Plugin\Caption\Type\CaptionAsset
	 */
	function add($entryId, \Kaltura\Client\Plugin\Caption\Type\CaptionAsset $captionAsset)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "captionAsset", $captionAsset->toParams());
		$this->client->queueServiceActionCall("caption_captionasset", "add", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Caption\\Type\\CaptionAsset");
		return $resultObject;
	}

	/**
	 * Update content of caption asset
	 *      
	 * 
	 * @return \Kaltura\Client\Plugin\Caption\Type\CaptionAsset
	 */
	function setContent($id, \Kaltura\Client\Type\ContentResource $contentResource)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "contentResource", $contentResource->toParams());
		$this->client->queueServiceActionCall("caption_captionasset", "setContent", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Caption\\Type\\CaptionAsset");
		return $resultObject;
	}

	/**
	 * Update caption asset
	 *      
	 * 
	 * @return \Kaltura\Client\Plugin\Caption\Type\CaptionAsset
	 */
	function update($id, \Kaltura\Client\Plugin\Caption\Type\CaptionAsset $captionAsset)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "captionAsset", $captionAsset->toParams());
		$this->client->queueServiceActionCall("caption_captionasset", "update", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Caption\\Type\\CaptionAsset");
		return $resultObject;
	}

	/**
	 * Serves caption by entry id and thumnail params id
	 * 	 
	 * 
	 * @return file
	 */
	function serveByEntryId($entryId, $captionParamId = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "entryId", $entryId);
		$this->client->addParam($kparams, "captionParamId", $captionParamId);
		$this->client->queueServiceActionCall('caption_captionasset', 'serveByEntryId', $kparams);
		$resultObject = $this->client->getServeUrl();
		return $resultObject;
	}

	/**
	 * Get download URL for the asset
	 * 	 
	 * 
	 * @return string
	 */
	function getUrl($id, $storageId = null)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->addParam($kparams, "storageId", $storageId);
		$this->client->queueServiceActionCall("caption_captionasset", "getUrl", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$resultObject = (string)$resultObject;
		return $resultObject;
	}

	/**
	 * Get remote storage existing paths for the asset
	 * 	 
	 * 
	 * @return \Kaltura\Client\Type\RemotePathListResponse
	 */
	function getRemotePaths($id)
	{
		$kparams = array();
		$this->client->addParam($kparams, "id", $id);
		$this->client->queueServiceActionCall("caption_captionasset", "getRemotePaths", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Type\\RemotePathListResponse");
		return $resultObject;
	}

	/**
	 * Serves caption by its id
	 * 	 
	 * 
	 * @return file
	 */
	function serve($captionAssetId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "captionAssetId", $captionAssetId);
		$this->client->queueServiceActionCall('caption_captionasset', 'serve', $kparams);
		$resultObject = $this->client->getServeUrl();
		return $resultObject;
	}

	/**
	 * Markss the caption as default and removes that mark from all other caption assets of the entry.
	 * 	 
	 * 
	 * @return
	 */
	function setAsDefault($captionAssetId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "captionAssetId", $captionAssetId);
		$this->client->queueServiceActionCall("caption_captionasset", "setAsDefault", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}

	/**
	 * 
	 * @return \Kaltura\Client\Plugin\Caption\Type\CaptionAsset
	 */
	function get($captionAssetId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "captionAssetId", $captionAssetId);
		$this->client->queueServiceActionCall("caption_captionasset", "get", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Caption\\Type\\CaptionAsset");
		return $resultObject;
	}

	/**
	 * List caption Assets by filter and pager
	 * 	 
	 * 
	 * @return \Kaltura\Client\Plugin\Caption\Type\CaptionAssetListResponse
	 */
	function listAction(\Kaltura\Client\Type\AssetFilter $filter = null, \Kaltura\Client\Type\FilterPager $pager = null)
	{
		$kparams = array();
		if ($filter !== null)
			$this->client->addParam($kparams, "filter", $filter->toParams());
		if ($pager !== null)
			$this->client->addParam($kparams, "pager", $pager->toParams());
		$this->client->queueServiceActionCall("caption_captionasset", "list", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		$this->client->validateObjectType($resultObject, "\\Kaltura\\Client\\Plugin\\Caption\\Type\\CaptionAssetListResponse");
		return $resultObject;
	}

	/**
	 * 
	 * @return
	 */
	function delete($captionAssetId)
	{
		$kparams = array();
		$this->client->addParam($kparams, "captionAssetId", $captionAssetId);
		$this->client->queueServiceActionCall("caption_captionasset", "delete", $kparams);
		if ($this->client->isMultiRequest())
			return $this->client->getMultiRequestResult();;
		$resultObject = $this->client->doQueue();
		$this->client->throwExceptionIfError($resultObject);
		return $resultObject;
	}
}
