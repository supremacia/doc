<?php
/**
 * Doc Factory for HTML
 * @copyright   Bill Rocha - http://plus.google.com/+BillRocha
 * @license     MIT & GLP2
 * @author      Bill Rocha - prbr@ymail.com
 * @version     0.0.1
 * @package     Limp
 * @access      public
 * @since       0.0.4
 *
 * ©NeosTag é marca registrada da NeosOrg e todos os direitos são reservados.
 */

namespace Limp\Doc;

class Html
{

    private $name =             '';
    private $cached =           false;

    private $pathHtml =         '';
    private $pathHtmlCache =    '';
    private $pathStyle =        '';
    private $pathScript =       '';

    private $header =           null;
    private $body =             [];
    private $footer =           null;

    private $styles =           [];
    private $scripts =          [];
    private $forceCompress =    false;

    static private $values =    [];
    private $jsvalues =         [];
    private $block =            [];
    private $content =          '';
    private $tag =              'x:';


    /* Construct of Doc
     *
     *
     */
    function __construct($name = 'default', $cached = false)
    {
        $this->name = $name;
        $this->cached = $cached;

        $this->pathHtml = _HTML;
        $this->pathHtmlCache = _HTML.'cache/';
        $this->pathStyle = _CSS;
        $this->pathScript = _JS;

        $this->header = $this->pathHtml.'header.html';
        //$this->body   = $this->pathHtml.'body.html';
        $this->footer = $this->pathHtml.'footer.html';
    }

    function body($v = null)
    { 
        if($v === null) return $this->body;
        $this->body[] = $this->pathHtml.$v.'.html';
        return $this;
    }

    function header($v = null)
    {
        if($v === null) return $this->header;
        $this->header = $this->pathHtml.$v.'.html';
        return $this;
    }

    function footer($v = null)
    {
        if($v === null) return $this->footer;
        $this->footer = $this->pathHtml.$v.'.html';
        return $this;
    }

    //Insert additional contents (ex.: data base), before produce
    function insertBlock($tag, $contents)
    {
        $this->block[$tag] = $contents;
        return $this;
    }

    function cached($b = true)
    {
        $this->cached = $b;
        return $this;
    }

    function render()
    {
        if($this->cached &&
            file_exists($this->pathHtmlCache.$this->name.'_cache.html'))
                return true;

        $this->content = file_get_contents($this->header);
        foreach($this->body as $b){
            $this->content .= file_get_contents($b);
        }
        $this->content .= file_get_contents($this->footer);

        if(_MODE == 'dev') $this->assets();
        if(_MODE == 'pro') {
            $this->assetsComp();
            $this->setContent(str_replace(["\r","\n","\t",'  '], '', $this->getContent()));
        }

        $this->produce();

        if($this->cached) file_put_contents($this->pathHtmlCache.$this->name.'_cache.html', $this->getContent());

        return $this;
    }

    /* Style list insert
    */
    function insertStyles($list)
    {
        if(!is_array($list)) $list = [$list];
        $this->styles = $list;
        return $this;
    }

    /* Javascript list insert
    */
    function insertScripts($list)
    {
        if(!is_array($list)) $list = [$list];
        $this->scripts = $list;
        return $this;
    }

    /* Força a compressão dos arquivos CSS e JS
     *      -- para facilitar durante o desenvolvimento e testes.
     */
    function forceCompress($b = true)
    {
        $this->forceCompress = $b;
        return $this;
    }

    private function assetsComp()
    {
        //CSS STYLES
        if(file_exists($this->pathStyle.$this->name.'_all.css') && !$this->forceCompress)
            $content = file_get_contents($this->pathStyle.$this->name.'_all.css');
        else {
            $content = '';
            foreach ($this->styles as $file) {
                $content .= exec('java -jar '.__DIR__.'/min/yc.jar "'.$this->pathStyle.$file.'.css"');
            }
            file_put_contents($this->pathStyle.$this->name.'_all.css', $content);
            $content = exec('java -jar '.__DIR__.'/min/yc.jar "'.$this->pathStyle.$this->name.'_all.css"');
            file_put_contents($this->pathStyle.$this->name.'_all.css', $content);
        }
        $this->val('style', '<style id="stylesheet_base">'.$content.'</style>');

        //JAVASCRIPTS
        if(file_exists($this->pathScript.$this->name.'_all.js') && !$this->forceCompress)
            $content = file_get_contents($this->pathScript.$this->name.'_all.js');
        else {
            $content = ';';
            foreach ($this->scripts as $file) {
                $content .= exec('java -jar '.__DIR__.'/min/yc.jar "'.$this->pathScript.$file.'.js"');
            }
            file_put_contents($this->pathScript.$this->name.'_all.js', $content);
            $content = exec('java -jar '.__DIR__.'/min/yc.jar "'.$this->pathScript.$this->name.'_all.js"');
            file_put_contents($this->pathScript.$this->name.'_all.js', $content);
        }
        $s = '<script id="javascript_base">var URL=\''._URL.'\'';
        if(isset($_SESSION['msg']) && $_SESSION['msg'] !== '') {
            $s .= ',xmsg="'.str_replace('"', "'", $_SESSION['msg']).'"';
           unset($_SESSION['msg']);
        }
        foreach ($this->jsvalues as $n=>$v) {
            $s .= ','.$n.'='.(is_string($v) ? '\''.$v.'\'' : $v);
        }
        $s .= ';';
        $this->val('script', $s.$content.'</script>');
    }


    /* Produção dos links ou arquivos compactados.
     * para Style e Javascript
     *
     * Em modo 'dev' gera somente os links;
     * Em modo 'pro' compacta e obfusca os arquivos e insere diretamente no HTML.
     */
    private function assets()
    {
        $s = '';
        foreach($this->styles as $id=>$f){
            $s .= '<link id="stylesheet_'.$id.'" rel="stylesheet" href="'._URL.'css/'.$f.'.css">'."\n\t";
        }
        $this->val('style', $s);

        $s = '<script id="javascript_base">var URL=\''._URL.'\'';
        if(isset($_SESSION['msg']) && $_SESSION['msg'] !== '') {
            $s .= ',xmsg="'.str_replace('"', "'", $_SESSION['msg']).'"';
            unset($_SESSION['msg']); 
        }
        foreach ($this->jsvalues as $n=>$v) {
            $s .= ','.$n.'='.(is_string($v) ? '\''.$v.'\'' : $v);
        }
        $s .= ';</script>';
        foreach($this->scripts as $id=>$f){
            $s .= "\n\t".'<script id="javascript_'.$id.'" src="'._URL.'js/'.$f.'.js"></script>';
        }
        $this->val('script', $s); //e($this);
    }

    /* SEND
     * Send headers & Output tris content
     *
     */
    function send() 
    {
        if(_MODE == 'pro'){ 
            ob_end_clean();
            ob_start('ob_gzhandler');
        }
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        header('Cache-Control: must_revalidate, public, max-age=31536000');
        header('X-Server: Qzumba/0.1.8.beta');//for safety ...
        header('X-Powered-By: NEOS PHP FRAMEWORK/1.3.0');//for safety ...

        if($this->cached &&
            file_exists($this->pathHtmlCache.$this->name.'_cache.html'))
                return $this->sendWithCach();
        else exit(eval('?>'.$this->content));
    }

    /* Send cached version of compilation
     *
     */
    function sendCache()
    {
        if(!file_exists($this->pathHtmlCache.$this->name.'_cache.html')) {
            $this->cached = true;
            return $this;
        }

        $this->setContent(file_get_contents($this->pathHtmlCache.$this->name.'_cache.html'));
        $this->send();
    }

    /** Teste
     * @todo testando send with CACHE in PHP!
     */
    protected function sendWithCach()
    {
        $cache = $this->pathHtmlCache.$this->name.'_cache.html';
        if(!file_exists($cache)) file_put_contents($cache, $this->content);
        include $cache;
        exit();
    }

    //Insere o conteúdo processado Html
    protected function setContent($content) 
    {
        $this->content = $content;
        return $this;
    }

    //Pega o conteúdo processado Html
    protected function getContent() 
    {
        return $this->content;
    }

    //Pega uma variável ou todas
    protected function getVar($var = null) 
    {
        //return ($var == null) ? $this->values : (isset($this->values[$var]) ? $this->values[$var] : false);
        $var = trim($var);
        return ($var == null) ? static::$values : (isset(static::$values[$var]) ? static::$values[$var] : false);
    }
    protected static function get($var = null)
    {
        return ($var == null) ? static::$values : (isset(static::$values[$var]) ? static::$values[$var] : false);
    }

    //Pega o conteúdo de um block
    protected function getBlock($name = null)
    {
        return ($name == null) ? $this->block : (isset($this->block[$name]) ? $this->block[$name] : false);
    }

    //Registra uma variável para o Layout
    function value($name, $value = null){ return $this->val($name, $value = null);}
    function val($name, $value = null) 
    {
        //if(is_string($name)) $this->values[$name] = $value;
        //if(is_array($name)) $this->values = array_merge($this->values, $name);

        if(is_string($name)) static::$values[$name] = $value;
        if(is_array($name)) static::$values = array_merge(static::$values, $name);
        return $this;
    }

    //Registra uma variável para o Javascript
    function jsvar($name, $value = null) 
    {
        if(is_string($name)) $this->jsvalues[$name] = $value;
        if(is_array($name)) $this->jsvalues = array_merge($this->jsvalues, $name);
        return $this;
    }

    /**
     * Renderiza o arquivo html.
     * Retorna um array com o produto da renderização ou 'ecoa' o resultado.
     *
     * @param bool $get Retorna o produto da renderização para um pós-tratamento
     * @return array|void
    */
    private function produce($php = false, $brade = false, $sTag = true)
    {
        //With blade ???
        if($brade) $this->setContent($this->blade($this->getContent()));

        //With ©NeosTag ???
        if($sTag) {
            $ponteiro = -1;
            $content = $this->getContent();

            //Loop de varredura para o arquivo HTML
            while($ret = $this->sTag($content, $ponteiro)){
                $ponteiro = 0 + $ret['-final-'];
                $vartemp = '';

                //constant URL
                if($ret['-tipo-'] == 'var' && $ret['var'] == 'url') $vartemp = _URL;
                elseif (method_exists($this, '_' . $ret['-tipo-'])) $vartemp = $this->{'_' . $ret['-tipo-']}($ret);

                //Incluindo o bloco gerado pelas ©NeosTags
                $content = substr_replace($this->getContent(), $vartemp, $ret['-inicio-'], $ret['-tamanho-']);
                $this->setContent($content);

                //RE-setando o ponteiro depois de adicionar os dados acima
                $ponteiro = $ret['-inicio-'];
            }//end while
        }//end ©NeosTag

        //Eval PHP in HTML
        if($php) $this->evalPHP();

        //returns the processed contents
        return $this->getContent();
    }

    /**
     * Scaner for ©NeosTag
     * Scans the file to find a ©NeosTag - returns an array with the data found ©NeosTag
     *
     * @param string $arquivo   file content
     * @param string $ponteiro  file pointer
     * @param string $tag       ©NeosTag to scan
     * @return array|false      array with the data found ©NeosTag or false (not ©NeosTag)
    */
    private function sTag(&$arquivo, $ponteiro = -1, $tag = null)
    {
        if($tag == null) $tag = $this->tag;
        $inicio = strpos($arquivo, '<'.$tag, $ponteiro + 1);
        if($inicio !== false){
            //get the type (<s:tipo ... )
            $x = substr($arquivo, $inicio, 25);
            preg_match('/(?<tag>\w+):(?<type>\w+|[\:]\w+)/', $x, $m);
            if(!isset($m[0])) return false;

            $ntag = $m[0];
            //the final ...
            $ftag = strpos($arquivo, '</' . $ntag . '>', $inicio);
            $fnTag   = strpos($arquivo, '/>', $inicio);
            $fn   = strpos($arquivo, '>', $inicio);

            //not  /> or </s:xxx>  = error
            if($fnTag === false && $ftag === false) return false;

            if($ftag !== false ) {
                if($fn !== false && $fn < $ftag){
                    $a['-content-'] = substr($arquivo, $fn+1, ($ftag - $fn)-1);
                    $finTag = $fn;
                    $a['-final-'] = $ftag + strlen('</'.$ntag.'>');
                } else return false;
            } elseif($fnTag !== false) {
                $a['-content-'] = '';
                $finTag = $fnTag;
                $a['-final-'] = $fnTag + 2;
            } else return false;

            //catching attributes
            preg_match_all('/(?<att>\w+)="(?<val>.*?)"/', substr($arquivo, $inicio, $finTag - $inicio), $atb);

            if(isset($atb['att'])){
                foreach ($atb['att'] as $k=>$v){
                    $a[$v] = $atb['val'][$k];
                }
            }

            //block data
            $a['-inicio-'] = $inicio;
            $a['-tamanho-'] = ($a['-final-'] - $inicio);
            $a['-tipo-'] = 'var';

            if(strpos($m['type'], ':') !== false) $a['-tipo-'] = str_replace (':', '', $m['type']);
            else $a['var'] = $m['type'];

            return $a;
        }
        return false;
    }

    /**
     * Scaner para Blade.
     * Retorna o conteúdo substituindo variáveis BLADE (@var_name).
     *
     * @param string $arquivo Conteúdo do arquivo a ser 'scaneado'
     * @return string         O mesmo conteudo com variáveis BLADE substituídas
    */
    private function blade($arquivo)
    {
        $t = strlen($arquivo) - 1;
        $ini = '';
        $o = '';

        for($i =0; $i <= $t; $i++){

            if($ini != '' && $ini < $i){
                if($arquivo[$i] == '@' && ($i - $ini) < 2) {
                    $o .= '@';
                    $ini = '';
                    continue;
                }
                if(!preg_match("/[a-zA-Z0-9\.:\[\]\-_()\/'$+,\\\]/",$arquivo[$i])){
                    $out1 = substr($arquivo, $ini+1, $i-$ini-1);
                    $out = rtrim($out1, ',.:');
                    $i += (strlen($out) - strlen($out1));

                    if($this->getVar($out)) $out = $this->getVar($out);
                    else {
                        restore_error_handler();
                        ob_start();
                        $ret = eval('return '.$out.';');
                        if(ob_get_clean() === '') $out = $ret;
                        else $out = '';
                    }
                    $o .= $out; //exit($o);
                    $ini = '';
                    if($arquivo[$i] != ' ') $i --;//retirando espaço em branco...
                }
            } elseif($ini == '' && $arquivo[$i] == '@') $ini = $i;
            else $o .= $arquivo[$i];
        }//end FOR
        return $o;
    }

    /**
     * _var
     * Insert variable data assigned in view
     * Parameter "tag" is the tag type indicator (ex.: <s:variable  . . . tag="span" />)
     *
     * @param array $ret ©NeosTag data array
     * @return string   Renderized Html
    */
    private function _var($ret) 
    {
        $v = $this->getVar($ret['var']);
        if(!$v) return '';
        //$ret['-content-'] .= $v;
        $ret['-content-'] .= '<?php echo Limp\Doc\HTML::get("'.trim($ret['var']).'")?>';

        //List type
        if(is_array($v)) return $this->_list($ret);

        return $this->setAttributes($ret);
    }

    /**
     * _list :: Create ul html tag
     * Parameter "tag" is the list type indicator (ex.: <s:_list  . . . tag="li" />)
     *
     * @param array $ret ©NeosTag data array
     * @return string|html
    */
    private function _list($ret)
    {
        if(!isset($ret['var'])) return '';
        $v = $this->getVar($ret['var']);
        if(!$v || !is_array($v)) return '';

        $tag = isset($ret['tag']) ? $ret['tag'] : 'li';
        $ret = $this->clearData($ret);

        //Tag UL and params. (class, id, etc)
        $o = '<ul';
        foreach($ret as $k=>$val){
            $o .= ' '.trim($k).'="'.trim($val).'"';
        }
        $o .= '>';
        //create list
        foreach ($v as $k=>$val){
            $o .= '<'.$tag.'>'.$val.'</'.$tag.'>';
        }
        return $o . '</ul>';
    }

    /**
     * _block :: insert content block
     * Parameter "name" is the name of block;
     *
     * @param array $ret ©NeosTag data array
     * @return string|html
    */
    private function _block($ret)
    {
        if(!isset($ret['name'])) return '';
        $ret['-content-'] .= $this->getBlock(trim($ret['name']));
        if($ret['-content-'] == false) return '';

        unset($ret['name']);
        return $this->setAttributes($ret);
    }

    /**
     * Create <select> HTML tag
     * configuration:
            $select['data'] = ['value'=>'display', 'val....];
            $select['default'] = '..value...';
            $LimpDocHtml->val('varName',$select);

            ---- in HTML file 
            <x::select data="varName" ...some attributes />
     *
     * @param array $ret ©NeosTag data array
     * @return string|html
    */
    private function _select($ret)
    {
        $var = $this->getVar($ret['data']);
        if(!$var) return false;

        $o = '';
        foreach($var['data'] as $k => $v) {
            $o .= '<option value="'.$k.'"'.($var['default'] == $k ? ' selected':'').'>'.$v.'</option>';
        }
        $ret['-content-'] = $o;
        $ret['tag'] = 'select';
        return $this->setAttributes($ret);
    }


    private function setAttributes($a)
    {
        $content = isset($a['-content-']) ? $a['-content-'] : '';
        $tag = isset($a['tag']) ? $a['tag'] : '';
        $a = $this->clearData($a);

        //Var span (with class, id, etc);
        if(count($a) > 0) {
            if($tag == '') $tag= 'span';
            $d = '<'.$tag;
            foreach ($a as $k=>$v){
                $d .= ' '.trim($k).'="'.trim($v).'"';
            }

            if($tag == 'input') $content = $d.' value="'.$content.'"/>';
            else $content = $d.'>'.$content.'</'.$tag.'>';

        } elseif($tag != '') {
            if($tag == 'input') $content = '<'.$tag.' value="'.$content.'"/>';
            else $content = '<'.$tag.'>'.$content.'</'.$tag.'>';
        }
        return $content;
    }


    /**
     * ClearData :: Clear all extra data.
     *
     * @param array $ret Starttag data array.
     * @return array Data array cleared.
    */
    function clearData($ret)
    {
        unset($ret['var'], 
              $ret['-inicio-'], 
              $ret['-tamanho-'], 
              $ret['-final-'], 
              $ret['-tipo-'], 
              $ret['-content-'], 
              $ret['tag'],
              $ret['data']);
        return $ret;
    }

}