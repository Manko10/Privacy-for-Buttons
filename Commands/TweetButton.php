<?php
/**
 * Command for generating Twitter's Tweet button.
 *
 * @package Pfb::Commands
 * @author Janek Bevendorff
 * @since 0.1
 */
class Pfb_Commands_TweetButton implements Pfb_Interfaces_Command
{
    /**
     * @since 0.1
     * @var Pfb_Interfaces_Request
     */
    private $request;
    
    /**
     * @since 0.1
     * @var Pfb_Interfaces_Response
     */
    private $response;
    
    /**
     * @since 0.1
     * @var Pfb_Interfaces_Model
     */
    private $model;
    
    /**
     * @since 0.1
     * @var string
     */
    private $lang;
    
    /**
     * Execute command (hook for FrontController)
     * 
     * @author Janek Bevendorff
     * @since 0.1
     * 
     * @param FrontController_Request $request
     * @param FrontController_Response $response
     * @return void
     */
    public function execute(Pfb_Interfaces_Request $request, Pfb_Interfaces_Response $response) {
        $url         = $request->getParam('url');
        $action      = $request->getParam('a');        
        $this->model = new Pfb_Models_TweetButtonModel($url);
        
        $this->lang = 'en';
        if (preg_match('#^[a-z]{2}$#', $request->getParam('lang'))) {
            $this->lang = $request->getParam('lang');
        }
        
        if (!$url && !$action) {
            $response->setStatus('404 Not Found');
            $view = new Pfb_FrontController_TemplateView('NotFound');
            $view->display($request, $response);
            return;
        }
        
        $this->request  = $request;
        $this->response = $response;
        
        if ($action) {
            $action = strtolower($action);
            switch ($action) {
                case 'bgimage':
                    $this->loadBgImage();
                    break;
            }
        } else {
            $this->loadButtonPage($url);
        }
    }
    
    /**
     * Process main button page.
     *
     * @author Janek bevendorff
     * @since 0.1
     *
     * @param string $url
     * @return void
     */
    protected function loadButtonPage($url) {
        $view  = new Pfb_FrontController_TemplateView('TweetButton');
        
        $locales = $this->model->getLocales($this->lang);
        if (null === $locales) {
            $this->lang = 'en';
            $locales = $this->model->getLocales($this->lang);
        }
        $view->assignVar('locales', $locales);
        $view->assignVar('count', $this->model->getCounter());
        $view->assignVar('url', $url);
        $view->assignVar('lang', $this->lang);
        $view->assignVar('path', $this->request->getPath());
        
        if (in_array($this->request->getParam('type'), array('vertical', 'horizontal', 'none'))) {
            $view->assignVar('type', $this->request->getParam('type'));
        } else {
            $view->assignVar('type', 'none');
        }
        
        $view->display($this->request, $this->response);
    }
    
    /**
     * Fetch background image.
     * 
     * @author Janek Bevendorff
     * @since 0.1
     * 
     * @return void
     */
    protected function loadBgImage() {
        $provider = new Pfb_Provider_HttpProvider();
        $img = $this->model->getBackgroundImage($this->lang);
        
        if (false === $img) {
            $view = new Pfb_FrontController_TemplateView('NotFound');
            $this->response->setStatus('404 Not Found');
            $view->display($this->request, $this->response);
            return;
        }
        
        // set aggressive browser caching of two weeks
        $this->response->addHeader('Expires', gmdate('D, d M Y H:i:s', time() + 1209600) . ' GMT');
        $this->response->addHeader('Cache-Control', 'max-age=1209600, public');
        
        $this->response->addHeader('Content-type', 'image/png');
        $this->response->write($img);
        $this->response->flush();
    }
}