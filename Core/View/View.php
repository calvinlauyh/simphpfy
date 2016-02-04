<?php

/* 
 * Created by Hei
 */

class View {
    /*
     * A flag indiciating whether a render call has been executed
     * 
     * @var Boolean
     */
    private $_isRendered = false;
    
    /*
     * The controller of the current request. If there is no controller, it is
     * set to null
     * 
     * @var String
     */
    private $_controller;
    
    /*
     * The name of the action of the current request. If there is no action, it 
     * is set to null
     * 
     * @var String
     */
    private $_action;
    
    /*
     * The directory to the template file
     * 
     * @var String
     */
    private $_directory;
    
    /*
     * The directory to the parsed template file
     */
    private $_parsedDirectory;
    
    /*
     * Constructor
     * 
     * @param Controller $controller, The reference to the controller
     */
    function __construct($controller) {
        $this->setController($controller);
        $this->setAction($controller->getAction());
        $this->setDirectory(VIEW . $controller->getController() . DS);
        $this->setParsedDirectory(TEMP_VIEW . $controller->getController() . DS);
    }
    
    /*
     * TODO:
     * Render an array or associative array to json string
     * 
     * @param Void
     */
    public function renderJSON($json) {
        $this->setIsRendered(true);
        if (($json = json_encode($json)) === FALSE) {
            throw new InvalidViewException('Error when encoding json');
        }
        echo $json;
    }
    
    /*
     * Redirect to the url
     * 
     * @param String $url, The url to redirect to
     * 
     * @return void
     */
    public function redirect($url) {
        $this->setIsRendered(true);
        header("Location: {$url}");
        die();
    }
    
    /*
     * Render a file
     * 
     * @param String $filePath, The path of the file
     * 
     * @return void
     * 
     */
    public function renderFile($filePath, $download=FALSE) {
        $this->setIsRendered(true);
        if (!file_exists($filePath)) {
            throw new InvalidViewException("`{$filePath}` was not found on the server");
        }
        $extension = substr(strrchr($filePath, "."), 1);
        header('Content-Type: ' . Mimetype::fromPath($filePath));
        if ($download) {
            header('Content-Disposition: attachment; filename='.basename($filePath));
        }
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        echo readfile($filePath);
    }
    
    /*
     * Automatically select a template inside app/view/:Controller/:Action.* 
     * and render it
     * 
     * @param String $fileName the name of the template file
     * 
     * @return String, Path the the parsed template file
     */
    public function renderTemplate() {
        $this->setIsRendered(true);
        if (file_exists($this->getDirectory().$this->getAction().'.html')) {
            return $this->renderHTML($this->getAction(), $this->getDirectory());
        } elseif (file_exists($this->getDirectory().$this->getAction().'.js')) {
            return $this->renderJS($this->getAction(), $this->getDirectory());
        } elseif (file_exists($this->getDirectory().$this->getAction().'.css')) {
            return $this->renderCSS($this->getAction(), $this->getDirectory());
        } else {
            throw new InvalidViewException("No template for `{$this->getAction()}` found in `{$this->getController()}`");
        }
    }
    
    /*
     * Render a layout file insider app/view/:Controller/
     * 
     * @params String $layoutName the name of the layout file
     * 
     * @return void
     */
    public function layout($layoutName){
        echo Template::render($layoutName, $this->getDirectory(), 
                array(
                    'dynamic' => FALSE, 
                    'parsedDirectory' => $this->getParsedDirectory(), 
                    'controller' => $this->getController(), 
                    'action' => $this->getAction()
                ));
    }
    
    /*
     * Render a JS template insider app/view/:Controller/:Action.html
     * 
     * @return String, The url to the parsed HTML
     */
    public function renderHTML() {
        $this->setIsRendered(true);
        return Template::render($this->getAction(), $this->getDirectory(), 
                array(
                    'format' => 'html', 
                    'parsedDirectory' => $this->getParsedDirectory(), 
                    'controller' => $this->getController(), 
                    'action' => $this->getAction()
                ));
    }
    
    /*
     * Render a JS template inside app/view/:Controller/:Action.js
     * 
     * @param String $templateName the name of the template without extension
     * 
     * @return void
     */
    public function renderJS() {
        $this->setIsRendered(true);
        return Template::render($this->getAction(), $this->getDirectory(), 
                array(
                    'format' => 'js', 
                    'parsedDirectory' => $this->getParsedDirectory(), 
                    'controller' => $this->getController(), 
                    'action' => $this->getAction()
                ));
    }
    
    /*
     * Render a CSS template inside app/view/:Controller/:Action.css
     * 
     * @param String $templateName the name of the template without extension
     * 
     * @return void
     */
    public function renderCSS() {
        $this->setIsRendered(true);
        return Template::render($this->getAction(), $this->getDirectory(), 
                array(
                    'format' => 'css', 
                    'parsedDirectory' => $this->getParsedDirectory(), 
                    'controller' => $this->getController(), 
                    'action' => $this->getAction()
                ));
    }
    
    // auto-generated getter and setter
    public function isRendered() {
        return $this->_isRendered;
    }
 
    private function getController() {
        return $this->_controller;
    }

    private function getAction() {
        return $this->_action;
    }

    private function getDirectory() {
        return $this->_directory;
    }
    
    private function getParsedDirectory() {
        return $this->_parsedDirectory;
    }
    
    private function setIsRendered($isRendered) {
        $this->_isRendered = $isRendered;
    }

    private function setController($controller) {
        $this->_controller = $controller;
    }

    private function setAction($action) {
        $this->_action = $action;
    }

    private function setDirectory($directory) {
        $this->_directory = $directory;
    }

    private function setParsedDirectory($parsedDirectory) {
        $this->_parsedDirectory = $parsedDirectory;
    }
}
