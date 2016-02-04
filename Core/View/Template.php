<?php

/* 
 * Created by Hei
 */

class Template{
    /*
     * The default parsed directory will be TEMP_VIEW . PARSEDDIRECTORY . DS
     */
    const PARSEDDIRECTORY = 'parsed';
    
    /*
     * The name of the template file
     * 
     * @var String
     */
    private $_templateName;
    
    /*
     * the path to the template file
     * 
     * @var String
     */
    private $_path;
    
    /*
     * the extension of the template file
     * 
     * @var String
     */
    private $_extension;
    
    /*
     * the parse directory
     * 
     * @var String
     */
    private $_parsedDirectory;
    
    /*
     * the name of the parsed template file
     * 
     * @var String
     */
    private $_parsedTemplateName;
    
    /*
     * the path to the parsed template path
     * 
     * @var String
     */
    private $_parsedPath;
    
    /*
     * the relative path to the parsed template path
     * 
     * @var String
     */
    private $_parsedRelativePath;
    
    /*
     * A copy of the $options
     * 
     * @var Array
     */
    private $_options;
    
    /*
     * Flag indicating the template has dynamic content
     * 
     * @var Boolean
     */
    private $_dynamic;
    
    /*
     * Flag indicating the template has to be minified
     * 
     * @var Boolean
     */
    private $_minify;
    
    /*
     * Flag indicating whehter to parse variable in the temlate file
     * 
     * @var Boolean
     */
    private $_parseVariable;
    
    /*
     * Flag indicating the template has been updated
     * 
     * @var Boolean
     */
    private $_updated;
    
    /*
     * The parsed content
     */
    private $_parsedContent;
    
    /*
     * constructor
     * 
     * @param String $templateName the name of the template file
     * @param String $directory the directory
     * @param Array $options list of options
     */
    public function __construct($templateName, $directory, $options=array()) {
        $this->setOptions($options);
        $path = $directory.$templateName;
        /* 
         * `format` set in $options implies that the $templateName is just the 
         * name of the template without extension and the `format` is the 
         * exntension. `format` in $options has higher priority is a mean to 
         * improve performance be replacing costly extension extraction from
         * $templateName
         */
        if (isset($options['format'])) {
            if ($options['format'] == 'html') {
                $templateName = $templateName.'.html';
                $path = $path.'.html';
                $extension = 'html';
            } elseif ($options['format'] == 'js') {
                $templateName = $templateName.'.js';
                $path = $path.'.js';
                $extension = 'js';
            } elseif ($options['format'] == 'css') {
                $templateName = $templateName.'.css';
                $path = $path.'.css';
                $extension = 'css';
            } else {
                throw new InvalidTemplateException(array($path, 'Unrecognized format in $options'));
            }
        } else {
            $extension = substr(strrchr($templateName, "."), 1);
            if ($extension != 'html' && $extension != 'js' && $extension != 'css') {
                throw new InvalidTemplateException(array($path, 'Unrecognized format in $options'));
            }
        }
        $this->setTemplateName($templateName);
        $this->setPath($path);
        $this->setExtension($extension);
        
        if (!file_exists($path)) {
            throw new MissingTemplateException(array($templateName, $directory));
        }
        
        // dynamic options
        if (isset($options['dynamic']) && is_bool($options['dynamic'])) {
            $dynamic = ($options['dynamic']);
        } else {
            $dynamic = TRUE;
        }
        $this->setDynamic($dynamic);
        
        // custom directory for parsed template file
        if (isset($options['parsedDirectory'])) {
            $parsedDirectory = $options['parsedDirectory'];
        } else {
            $parsedDirectory = TEMP_VIEW . self::PARSEDDIRECTORY . DS;
        }
        $parsedTemplateName = $templateName . '.php';
        $parsedPath = $parsedDirectory . $parsedTemplateName;
        $parsedRelativePath = SIMPHPFY_RELATIVE_PATH . '__WebDocument' . DS . urlencode(base64_encode($parsedPath));
        $this->setParsedDirectory($parsedDirectory);
        $this->setParsedTemplateName($parsedTemplateName);
        $this->setParsedPath($parsedPath);
        $this->setParsedRelativePath($parsedRelativePath);
        
        /*
         * Check if update is required for the template file by comparing the
         * file modification time of two files and return true if two files has 
         * different last modification time (i.e. update is required)
         */
        $isUpdateRequired = TRUE;
        $pathMTime = filemtime($path);
        if (file_exists($parsedPath)) {
            if ($pathMTime == filemtime($parsedPath)) {
                $isUpdateRequired = FALSE;
            }
        }
        $this->setUpdated($isUpdateRequired);
        if ($isUpdateRequired) {
            $file = null;
            if (!is_readable($directory)) {
                throw new InvalidTemplateException(array($path, 'the directory is not readable'));
            }
            if (!($file = fopen($path, 'rb'))) {
                throw new InvalidTemplateException(array($path, 'Unable to get content from the file'));
            }
            // use fread to read file memory-safely
            $content = fread($file, filesize($path));
            fclose($file);
            
            $parsedContent = $content;
            
            /* 
             * extract minify and parseVariable options
             * 
             * $options['minify'] controls whether the document has to be
             * minified
             */
            if (isset($options['minify'])) {
                $minify = $options['minify'];
            } else {
                $minify = TRUE;
            }
            $this->setMinify($minify);
            /*
             * $options['parseVariable'] controls whether to parse variable in
             * the document in the form ${variable_name}
             */
            if (isset($options['parseVariable'])) {
                $parseVariable = $options['parseVariable'];
            } else {
                $parseVariable = TRUE;
            }
            $this->setParseVariable($parseVariable);
            
            /*
             * parse dynamic content
             */
            if ($dynamic) {
                $parsedContent = $this->parse($parsedContent);
            } else {
                // minify JavaScript
                if ($extension == 'js' && $minify) {
                    $parsedContent = JSMin::minify($parsedContent);
                }
            }
            
            /*
             * Universal parsing rule regardless of dynamic content or not
             */
            // minify HTML
            if ($extension == 'html' && $minify) {
                $parsedContent = $this->minifyHTML($parsedContent);
            }
            
            /*
             * Save the file
             */
            $templateFile = null;
            
            if (!file_exists($parsedDirectory) && !mkdir($parsedDirectory, 0777, true)) {
                throw new InvalidTemplateException(array($parsedPath, 'the directory does not exist and is not creatable'));
            }
            if (!is_writable($parsedDirectory)) {
                throw new InvalidTemplateException(array($parsedPath, 'the directory is not writable'));
            }
            if(!($templateFile = fopen($parsedPath, 'w+'))){
                throw new InvalidTemplateException(array($parsedPath, 'Unable to open/create the file'));
            }
            if(!fwrite($templateFile, $parsedContent)){
                throw new InvalidTemplateException(array($parsedPath, 'Unable to save the file'));
            }
            if (!touch($parsedPath, $pathMTime)) {
                throw new InvalidTemplateException(array($parsedPath, 'Unable to change the modification time of the file'));
            }
            
            $this->setParsedContent($parsedContent);
        }
    }
    
    /*
     * Parse the template content with respect to rules
     * 
     * @param String $content The template content to be parsed
     * 
     * @return String The parsed content
     */
    private function parse($content) {
        $extension = $this->getExtension();
        $path = $this->getPath();
        $parseVariable = $this->isParseVariable();
        $options = $this->getOptions();
        /*
         * Everything inside a <% IGNORE %>...<% IGNOER_END %> block
         * will be ignored from being parsed
         */
        $ignoreBlock = array();
        $content = preg_replace_callback('@(<% *IGNORE *%>(.*?)<% *IGNORE_END *%>)@s', 
            function($matches) use(&$ignoreBlock) {
                $ignoreBlock[] = $matches[2];
                return '<!--SimPHPfyIgnoreBlock#'.count($ignoreBlock).'#SimPHPfyIgnoreBlock-->';
            }, $content);
        
        /* 
         * Code block is in the form <% code %>, code can be any valid
         * PHP statements.
         * A special form of code block is <%= ${variable_name} %> 
         * which is equivalent to echo a variable
         * 
         * Every code snippet is stored inside the $codeBlock Array to
         * prevent variable parsing from corrupting the whole document
         */
        $codeBlock = array();
        $content = preg_replace_callback('@(<%= *(.*?[^\\\\]) *%>)@s', 
            function($matches) use (&$codeBlock) {
                $codeSnippet = $matches[2];
                if (preg_match('@^\${([a-zA-Z_\x7f-\xff][\[\]\'"a-zA-Z0-9_\x7f-\xff*]*(\[[\'"][a-zA-Z0-9_\x7f-\xff*]*[\'"]\])*)}$@', $codeSnippet)) {
                    $codeSnippet = "<?php if (isset({$codeSnippet})) { echo {$codeSnippet}; } ?>";
                } elseif (preg_match('@^\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff*]*(\[[\'"][a-zA-Z0-9_\x7f-\xff*]*[\'"]\])*)$@', $codeSnippet)) {
                    $codeSnippet = "<?php if (isset({$codeSnippet})) { echo {$codeSnippet}; } ?>";
                }
                $codeBlock[] = $codeSnippet;
                return '<!--SimPHPfyCodeBlock#' . count($codeBlock) . '#SimPHPfyCodeBlock-->';
            }, $content);
        $content = preg_replace_callback('@(<% *(.*?[^\\\\]) *%>)@s', 
            function($matches) use (&$codeBlock, $path) {
                $codeSnippet = $matches[2];
                if ($codeSnippet == 'IGNORE_MINIFY' || $codeSnippet == 'IGNORE_MINIFY_END') {
                    return $matches[0];
                } elseif(preg_match('@^render.*$@', $codeSnippet)) {
                    /*
                     * Layout rendering
                     */
                    $codeSnippet = '<' . $codeSnippet . ' />';
                    $dom = new DOMDocument();
                    @$dom->loadXML($codeSnippet);
                    /*
                     * extract the attributes of render tag
                     */
                    $render = $dom->getElementsByTagName('render')->item(0);
                    // <% render file='header.html' directory='' static='' dynamic='' %>
                    $file = $directory = $dynamic = $format = $parsedDirectory = '';
                    if (($file = $render->getAttribute('file')) == '') {
                        throw new InvalidTemplateException(array($path, 'Missing `file` for layout rendering'));
                    }
                    $directory = ASSERT;
                    $parsedDirectory = TEMP_VIEW . 'Assert' . DS;
                    if ($dirAttr = $render->getAttribute('directory') != '') {
                        $directory .= $dirAttr . DS;
                        $parsedDirectory = TEMP_VIEW . $dirAttr . DS;
                    }
                    /*
                     * If `controller` attribute is spceified, the layout is 
                     * inside the View/:Controller
                     */
                    if (($controllerAttr = $render->getAttribute('controller')) != '') {
                        $directory = VIEW . $controllerAttr . DS;
                        $parsedDirectory = TEMP_VIEW . $controllerAttr . DS;
                    }
                    if (($staticAttr = $render->getAttribute('static')) != '') {
                        $dynamic = !$staticAttr;
                    }
                    /*
                     * `dynamic` attribute always overwrite `static` attribute, 
                     * thought it is not recommended to set both attributes
                     */
                    if (($dynamicAttr = $render->getAttribute('dynamic')) != '') {
                        $dynamic = $dynamicAttr;
                    }
                    /*
                     * The behaviour of render. When a render code is parsed, 
                     * should it return a URL to the rendered file or directly 
                     * render that file, possiblities: 'url' | 'direct'
                     * default: 'direct'
                     */
                    if (($behaviourAttr = $render->getAttribute('behaviour')) == '') {
                        $behaviour = 'direct';
                    } else {
                        $behaviour = $behaviourAttr;
                    }
                    $format = $render->getAttribute('format');
                    
                    $optionArray = 'array(';
                    $i = 0;
                    if ($format != '') {
                        $optionArray .= "'format' => {$format}";
                        $i++;
                    }
                    if ($parsedDirectory != '') {
                        $optionArray .= (($i++==0)?'':', ') . "'parsedDirectory' => '{$parsedDirectory}'";
                    }
                    if ($dynamic !== '') {
                        $optionArray .= (($i++==0)?'':', ') . '\'dynamic\' => ' . (($dynamic)? 'TRUE': 'FALSE');
                    } else {
                        $dynamic = TRUE;
                    }
                    $optionArray .= ')';
                    
                    $extension = substr(strrchr($file, "."), 1);
                    if ($behaviour == 'direct' || $dynamic) {
                        if ($extension == 'html') {
                            $codeSnippet = "<?php include Template::render('{$file}', '{$directory}', {$optionArray}); ?>";
                        } elseif ($extension == 'js') {
                            $codeSnippet = "<script><?php include Template::render('{$file}', '{$directory}', {$optionArray}); ?></script>";
                        } elseif ($extension == 'css') {
                            $codeSnippet = "<style><?php include Template::render('{$file}', '{$directory}', {$optionArray}); ?></style>";
                        }
                    } else {
                        if ($extension == 'html') {
                            $codeSnippet = "<?php include Template::render('{$file}', '{$directory}', {$optionArray}); ?>";
                        } else{
                            $optionArray = substr($optionArray, 0, -1) . (($i++==0)?'':', ') . '\'relative\' => TRUE)';
                            if ($extension == 'js') {
                                $codeSnippet = "<script src=\"<?php echo Template::render('{$file}', '{$directory}', {$optionArray}); ?>\"></script>";
                            } elseif ($extension == 'css') {
                                $codeSnippet = "<link rel=\"stylesheet\" href=\"<?php include Template::render('{$file}', '{$directory}', {$optionArray}); ?>\" />";
                            }
                        }
                    }
                } else {
                    $codeSnippet = '<?php ' . str_replace('\%>', '%>', $codeSnippet) . ' ?>';
                }
                $codeBlock[] = $codeSnippet;
                return '<!--SimPHPfyCodeBlock#'.count($codeBlock).'#SimPHPfyCodeBlock-->';
            }, $content);
            
        /* 
         * Perform HTML specificied parsing
        */
        if ($extension == 'html') {
            $htmlBlock = array();
            
            /* 
             * gurantee the form tag is not inside a comment by examining the 
             * DOM Tree instead of pure regular expression
             */
            $dom = new DOMDocument();
            @$dom->loadHTML($content);
            $forms = $dom->getElementsByTagName('form');;
            for($i=0; $i<$forms->length; $i++) {
                /*
                 * Re-create the DOM because the previous iteration have
                 * changed the content of $content
                 */
                $dom = new DOMDocument();
                @$dom->loadHTML($content);
                $form = $dom->getElementsByTagName('form')->item($i);
                if ($form->textContent == '') {
                    /*
                     * Create dummy block to make the form tag recognizable
                     */
                    $dummyString = '<!--SimPHPfyDummyBlock#' . $i . '#SimPHPfyDummyBlock-->';
                    $textNode = $dom->createTextNode($dummyString);
                    $form->appendChild($textNode);
                    $content = $dom->saveHTML();
                    $pattern = '@(< *form.*?>)(' . $dummyString.')@';
                } else {
                    /*
                     * Save the form HTML content into the $htmlBlock
                     */
                    $htmlContent = '';
                    while($form->hasChildNodes()) {
                        /*
                         * Loop through the child to skip the parent form tag
                         * from including into the stored $htmlBlock
                         */
                        $node = $form->childNodes->item(0);
                        $htmlContent .= $dom->saveXML($node);
                        $form->removeChild($node);
                    }
                    $htmlBlock[] = $htmlContent;
                    $commentNode = $dom->createComment('SimPHPfyHTMLBlock#'.count($htmlBlock).'#SimPHPfyHTMLBlock');
                    $htmlBlockString = '<!--SimPHPfyHTMLBlock#'.count($htmlBlock).'#SimPHPfyHTMLBlock-->';
                    $form->appendChild($commentNode);
                    $content = $dom->saveHTML();
                    $pattern = '@(< *form.*?>)(' . $htmlBlockString .')@';
                }
                $content = preg_replace_callback($pattern, 
                    function($matches) use ($options, $path) {
                $formDom = new DOMDocument();
                @$formDom->loadHTML($matches[1]);
                $formTags = @$formDom->getElementsByTagName('form');
                $formTag = $formTags->item(0);
                $controller = $action = '';
                /*
                 * data-controller and data-action attributes determine which 
                 * controller and action the form should send to
                 * 
                 * A table of the possible values of data-* are as followed:
                 * data-controller  data-action     result
                 * String           String          $contoller/$action
                 * Omitted          String          :Controller/$action
                 * String           Omitted         Disallowed
                 * Omitted          Omitted         :Controller/:Action
                 */
                if ($formTag->getAttribute('data-helper') == 'simphpfy') {
                    if ($formTag->getAttribute('data-controller') == '') {
                        if ($formTag->getAttribute('data-action') == '') {
                            if (isset($options['controller'])) {
                                $controller = $options['controller']->getController();
                                $action = $options['controller']->getAction();
                            } else {
                                throw new InvalidTemplateException(array($path, 'missing controller and/or action for form helper'));
                            }
                        } else {
                            if (isset($options['controller'])) {
                                $controller = $options['controller']->getController();
                                $action = $formTag->getAttribute('data-action');
                            } else {
                                throw new InvalidTemplateException(array($path, 'missing controller for form helper'));
                            }
                        }
                    } else {
                        if ($formTag->getAttribute('data-action') == '') {
                            throw new InvalidTemplateException(array($path, 'malformed form helper'));
                        } else {
                            $controller = $formTag->getAttribute('data-controller');
                            $action = $formTag->getAttribute('data-action');
                        }
                    }
                    if ($controller && $action) {
                        $actionAttr = DIRECTORY_PREFIX . $controller . DS . $action;
                        if (($id = $formTag->getAttribute('data-id')) != '') {
                            $actionAttr .= DS . $id;
                        }
                        $formTag->setAttribute('action', $actionAttr);
                    } else {
                        throw new InvalidTemplateException(array($path, 'malformed form helper'));
                    }
                    // check if method is PUT or DELETE                
                    if ($formTag->getAttribute('data-method') != '') {
                        $method = strtoupper($formTag->getAttribute('data-method'));
                        if ($method == "PUT" || $method == "DELETE") {
                            $formTag->setAttribute('method', 'POST');
                        } elseif ($method == 'GET' || $method == 'POST') {
                            $formTag->setAttribute('method', $method);
                        }
                        $inputMethod = $formDom->createElement('input');
                        $inputMethod->setAttribute('type', 'hidden');
                        $inputMethod->setAttribute('name', '_method');
                        $inputMethod->setAttribute('value', $method);
                        $formTag->appendChild($inputMethod);
                    }
                    // remove <!DOCTYPE 
                    $formDom->removeChild($formDom->doctype);
                    // remove <html><body></body></html> 
                    $formDom->replaceChild($formDom->firstChild->firstChild->firstChild, $formDom->firstChild);
                    return str_replace('</form>', '', $formDom->saveHTML()) . $matches[2];
                } else {
                    return $matches[0];
                }
                    }, $content);
            }
            $content = preg_replace_callback('@<!--SimPHPfyHTMLBlock#([0-9]+)#SimPHPfyHTMLBlock-->@', 
                function($matches) use($htmlBlock) {
                    return $htmlBlock[(int) $matches[1]-1];
                }, $content);
            $dom = new DOMDocument();
            @$dom->loadHTML($content);
            $this->parseInput($dom->getElementsByTagName('input'), $codeBlock);
            $this->parseInput($dom->getElementsByTagName('select'), $codeBlock);
            $this->parseInput($dom->getElementsByTagName('textarea'), $codeBlock);
            $content = $dom->saveHTML();
        }
        if ($parseVariable) {
            $content = preg_replace('@([^\\\\]|]^)\${([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)}\$@', '<?php echo $\2; ?>', $content);
            $content = preg_replace('@([^\\\\]|^)\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\$@', '<?php echo $\2; ?>', $content);
        }
        $content = preg_replace_callback('@<!--SimPHPfyIgnoreBlock#([0-9]+)#SimPHPfyIgnoreBlock-->@', 
            function($matches) use($ignoreBlock) {
                return $ignoreBlock[(int) $matches[1]-1];
            }, $content);
        $content = preg_replace_callback('@<!--SimPHPfyCodeBlock#([0-9]+)#SimPHPfyCodeBlock-->@', 
            function($matches) use($codeBlock) {
                return $codeBlock[(int) $matches[1]-1];
            }, $content);
        $content = preg_replace('@<!--SimPHPfyDummyBlock#([0-9]+)#SimPHPfyDummyBlock-->@', '', $content); 
        return $content;
    }
    
    /*
     * Parse all input field in the HTML content
     * 
     * @param String $content, The HTML string to be rendered
     * 
     * @return String, The parsed HTML content
     */
    private function parseInput($inputs, &$codeBlock) {
        $options = $this->getOptions();
        $path = $this->getPath();
        foreach($inputs as $input) {
            /*
             * data-model and data-column attributes determine which model
             * column the input should be
             * 
             * A table of the possible values of data-* are as followed:
             * data-model       data-column     result
             * String           String          $contoller/$column
             * Omitted          String          :Controller/$column
             */
            if ($input->getAttribute('data-helper') == 'simphpfy') {
                if (!isset($options['controller'])) {
                    throw new InvalidTemplateException(array($path, 'missing controller for input helper'));
                }
                if ($input->getAttribute('data-model') == '') {
                    if ($input->getAttribute('data-column') == '') {
                        throw new InvalidTemplateException(array($path, 'missing column for input helper'));
                    }
                    $model = $options['controller']->Model->getModel();
                    $column = $input->getAttribute('data-column');
                } else {
                    if ($input->getAttribute('data-column') == '') {
                        throw new InvalidTemplateException(array($path, 'missing column for input helper'));
                    }
                    $model = $input->getAttribute('data-model');
                    $column = $input->getAttribute('data-column');
                }
                /*
                 * Check the Model schema for the $model.$column existence
                 * 
                 * Noted that in the design of the template the checking of
                 * Model schema is performed when the template is rendered.
                 * If the schema has any modifications in the future, the
                 * checking will NOT be performed again. 
                 * 
                 * This design is made base on an assumption that
                 */
                $codeBlock[] = "<?php 
                    \$_simphpfy = \$this;
                        if(!isset(\$_simphpfy->Model->{$model}->getSchema()['columns']['{$column}'])) { 
                    throw new InvalidModelException(array('Unrecognized column `{$column}` in Model `{$model}`'));
                        } else { 
                    echo 'Model[{$model}][{$column}]';
                        } 
                    ?>";
                $input->setAttribute('name', '<!--SimPHPfyCodeBlock#'.count($codeBlock).'#SimPHPfyCodeBlock-->');
            }
        }
    }
    
    /*
     * Minify HTML string
     * 
     * @param String $content The HTML string to be minified
     * 
     * @return String The parsed HTML content
     */
    private function minifyHTML($content) {
        // preserve content formatting surrounded by single pair <pre>...</pre> tag
        $preBlock = array();
        $ignoreMinifyBlock = array();
        $content = preg_replace_callback('@(<pre.*?>.*?</pre>)@s', 
            function($matches) use(&$preBlock) {
                $preBlock[] = $matches[1];
                return '<!--SimPHPfyPreBlock#' . count($preBlock) . '#SimPHPfyPreBlock-->';
            }, $content);
            
        $content = preg_replace_callback('@(<% *IGNORE_MINIFY *%>(.*?)<% *IGNORE_MINIFY_END *%>)@s', 
            function($matches) use(&$ignoreMinifyBlock) {
                $ignoreMinifyBlock[] = $matches[2];
                return '<!--SimPHPfyIgnoreMinify#' . count($ignoreMinifyBlock) . '#SimPHPfyIgnoreMinify}';
            }, $content);
            
        // linebreak in Windows based systems
        $content = preg_replace("@\t+@", ' ', $content);
        $content = preg_replace('@(    )+@', ' ', $content);
        $content = str_replace("\r\n", '', $content);
        // linebreak in Unix based systems
        $content = str_replace("\n", ' ', $content);
        // linebreak in Macintosh based systems
        $content = str_replace("\r", ' ', $content);
        
        $content = preg_replace_callback('@<!--SimPHPfyPreBlock#([0-9]+)#SimPHPfyPreBlock-->@', 
            function($matches) use($preBlock) {
                return $preBlock[(int) $matches[1]-1];
            }, $content);
        $content = preg_replace_callback('@<!--SimPHPfyIgnoreMinify#([0-9]+)#SimPHPfyIgnoreMinify}@', 
            function($matches) use($ignoreMinifyBlock) {
                return $ignoreMinifyBlock[(int) $matches[1]-1];
            }, $content);
        return $content;
    }
    
    /*
     * Render a template file and return the rendered file path
     * 
     * List of available $options
     * $options = Array(
     *      'format' Explicitly specify he format of the template file, can
     *              enhance the performance, default: (auto-detect)
     *      'dynamic' Flag indiciating whether the template contains any
     *              dynamic content, default: TRUE
     *      'header' Flag indicating whether to send the header conatining the
     *              mime-type, default: (auto-detect)
     *      'parsedDirectory' The directory to the parsed template file, 
     *              default: (self::TEMPDIRECTORY)
     *      'parseVariable' Flag indicating whether to parse variable in 
     *              template file, i.e. $var
     *              default: TRUE
     *      'minify' Trim all space, line break and tab, if your template file
     *              has format prserved content, set minify to FALSE to prevent
     *              the render function affecting those code
     *              By default, when minify is set to TRUE, any content inside 
     *              a pair of <pre>...</pre> <ignoreMinify>...</ignoreMinify>
     *              will be ignored. Noted that this does NOT support recursive 
     *              <pre> and <ignoreMinify> tag such as
     *              <pre>
     *                  <pre>
     *                      ...
     *                  </pre>
     *                  ...
     *              </pre>
     *              Result:
     *              <pre>
     *                  <pre>
     *                      ...
     *                  </pre> </pre>
     *              default: TRUE
     *      'controller' The contoller object
     *      'relative' Flag indicating whether to return a realtive path or a
     *              full path, default: FALSE
     * )
     * 
     * @param String $templateName the name of the template file
     * @param String $directory the directory
     * @param Array $options list of options
     * 
     * @return String The path to the parse template file
     */
    public static function render($templateName, $directory, $options=NULL) {
        $template = new Template($templateName, $directory, $options);
        if (isset($options['header']) && $options['header']) {
            header('Content-Type: ' . Mimetype::fromExtension($this->getExtension()));
        }
        if (isset($options['relative']) && $options['relative']) {
            return $template->getParsedRelativePath();
        } else {
            return $template->getParsedPath();
        }
    }
    
    // auto-generated getter and setter
    public function getTemplateName() {
        return $this->_templateName;
    }

    public function getPath() {
        return $this->_path;
    }

    public function getExtension() {
        return $this->_extension;
    }

    public function getParsedDirectory() {
        return $this->_parsedDirectory;
    }

    public function getParsedTemplateName() {
        return $this->_parsedTemplateName;
    }

    public function getParsedPath() {
        return $this->_parsedPath;
    }
    
    public function getParsedRelativePath() {
        return $this->_parsedRelativePath;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function isDynamic() {
        return $this->_dynamic;
    }

    public function isMinify() {
        return $this->_minify;
    }

    public function isParseVariable() {
        return $this->_parseVariable;
    }

    public function isUpdated() {
        return $this->_updated;
    }

    public function getParsedContent() {
        return $this->_parsedContent;
    }

    private function setTemplateName($templateName) {
        $this->_templateName = $templateName;
    }

    private function setPath($path) {
        $this->_path = $path;
    }

    private function setExtension($extension) {
        $this->_extension = $extension;
    }

    private function setParsedDirectory($parsedDirectory) {
        $this->_parsedDirectory = $parsedDirectory;
    }

    private function setParsedTemplateName($parsedTemplateName) {
        $this->_parsedTemplateName = $parsedTemplateName;
    }

    private function setParsedPath($parsedPath) {
        $this->_parsedPath = $parsedPath;
    }

    private function setParsedRelativePath($parsedRelativePath) {
        $this->_parsedRelativePath = $parsedRelativePath;
    }
 
    private function setOptions($options) {
        $this->_options = $options;
    }

    private function setDynamic($dynamic) {
        $this->_dynamic = $dynamic;
    }

    private function setMinify($minify) {
        $this->_minify = $minify;
    }

    private function setParseVariable($parseVariable) {
        $this->_parseVariable = $parseVariable;
    }

    private function setUpdated($updated) {
        $this->_updated = $updated;
    }

    private function setParsedContent($parsedContent) {
        $this->_parsedContent = $parsedContent;
    }


}