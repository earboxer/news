<?php
/**
 * Nextcloud - News
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author    Alessandro Cosentino <cosenal@gmail.com>
 * @author    Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright 2012 Alessandro Cosentino
 * @copyright 2012-2014 Bernhard Posselt
 */

namespace OCA\News\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http;
use \OCP\AppFramework\Http\JSONResponse;

use \OCA\News\Http\TextDownloadResponse;
use \OCA\News\Service\FolderService;
use \OCA\News\Service\FeedService;
use \OCA\News\Service\ItemService;
use \OCA\News\Utility\OPMLExporter;

class ExportController extends Controller
{

    private $opmlExporter;
    private $folderService;
    private $feedService;
    private $itemService;
    private $userId;

    public function __construct($appName,
        IRequest $request,
        FolderService $folderService,
        FeedService $feedService,
        ItemService $itemService,
        OPMLExporter $opmlExporter,
        $UserId
    ) {
        parent::__construct($appName, $request);
        $this->feedService = $feedService;
        $this->folderService = $folderService;
        $this->opmlExporter = $opmlExporter;
        $this->itemService = $itemService;
        $this->userId = $UserId;
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function opml()
    {
        $feeds = $this->feedService->findAll($this->userId);
        $folders = $this->folderService->findAll($this->userId);
        $opml = $this->opmlExporter->build($folders, $feeds)->saveXML();
        $date = date('Y-m-d');
        $name = "subscriptions-".$date.".opml";
        $mimeType = 'text/xml';
        return new TextDownloadResponse($opml, $name, $mimeType);
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function articles()
    {
        $feeds = $this->feedService->findAll($this->userId);
        $items = $this->itemService->getUnreadOrStarred($this->userId);

        // build assoc array for fast access
        $feedsDict = [];
        foreach($feeds as $feed) {
            $feedsDict['feed' . $feed->getId()] = $feed;
        }

        $articles = [];
        foreach($items as $item) {
            $articles[] = $item->toExport($feedsDict);
        }

        $response = new JSONResponse($articles);
        $response->addHeader(
            'Content-Disposition',
            'attachment; filename="articles.json"'
        );
        return $response;
    }


}