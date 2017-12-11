<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 *
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use RuntimeException;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel();
    }

    public function crashAction()
    {
        throw new RuntimeException('Zend crashed!  Go and check your Bugsnag dashboard for a notification!');
    }

    public function callbackAction()
    {
        $GLOBALS['bugsnag']->registerCallback(function ($report) {
            $report->setMetaData([
                'account' => [
                    'name' => 'Acme Co.',
                    'paying_customer' => true,
                ],
            ]);
        });

        throw new RuntimeException('Zend crashed!  Go and check your Bugsnag dashboard for a notification with meta data!');
    }

    public function notifyAction()
    {
        $GLOBALS['bugsnag']->notifyError('Broken', 'Something broke');

        return new ViewModel();
    }

    public function notifymetadataAction()
    {
        $GLOBALS['bugsnag']->notifyError('Broken', 'Something broke', function ($report) {
            $report->setMetaData([
                'account' => [
                    'name' => 'Acme Co.',
                    'paying_customer' => true,
                ],
                'diagnostics' => [
                    'status' => 'cool',
                ],
            ]);
        });

        return new ViewModel();
    }

    public function notifyseverityAction()
    {
        $GLOBALS['bugsnag']->notifyError('Broken', 'Something broke', function ($report) {
            $report->setSeverity('info');
        });

        return new ViewModel();
    }
}
