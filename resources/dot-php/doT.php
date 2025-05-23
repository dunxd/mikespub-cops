<?php
/**
 * PHP renderer for doT templating engine
 *
 * @license    GPL v2 or later (https://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Template;

class doT
{
    public string $functionBody;
    /** @var callable */
    protected $functionCode;
    /** @var array<mixed> */
    public $def;

    /**
     * Summary of resolveDefs
     * @param ?string $block
     * @return ?string
     */
    public function resolveDefs($block)
    {
        $me = $this;
        return preg_replace_callback("/\{\{#([\s\S]+?)\}\}/", function ($m) use ($me) {
            $d = $m[1];
            $d = substr($d, 4);
            if (!array_key_exists($d, $me->def)) {
                return "";
            }
            if (preg_match("/\{\{#([\s\S]+?)\}\}/", (string) $me->def [$d])) {
                //return $me->resolveDefs($me->def [$d], $me->def);
                return $me->resolveDefs($me->def [$d]);
            } else {
                return $me->def [$d];
            }
        }, $block);
    }

    /**
     * Summary of handleDotNotation
     * @param string $string
     * @return ?string
     */
    public function handleDotNotation($string)
    {
        $out = preg_replace("/(\w+)\.(.*?)([\s,\)])/", "\$$1[\"$2\"]$3", $string);
        $out = preg_replace("/(\w+)\.([\w\.]*?)$/", "\$$1[\"$2\"] ", $out);
        $out = preg_replace("/\./", '"]["', $out);

        // Special hideous case : shouldn't be committed
        $out = preg_replace("/^i /", ' $i ', $out);
        return $out;
    }

    /**
     * Summary of template
     * @param string $string
     * @param ?array<mixed> $def
     * @return \Closure
     */
    public function template($string, $def)
    {
        $me = $this;

        $func = $string;

        // deps
        if (empty($def)) {
            $func = preg_replace("/\{\{#([\s\S]+?)\}\}/", "", $func);
        } else {
            $this->def = $def;
            $func = $this->resolveDefs($func);
        }

        // single quotes can mess up serverside rendering for client-side javascript, e.g. in header template:
        // <a href="#" onclick='Cookies.set("template", "default", { expires: 365 }); window.location.reload(true); ' ...
        $replace = [
            "unsafe" => ["'", '\/'],
            "encode" => ['~QUOTE~', '~SLASH~'],
        ];
        $func = str_replace($replace["unsafe"], $replace["encode"], $func);

        // interpolate
        $func = preg_replace_callback("/\{\{=([\s\S]+?)\}\}/", function ($m) use ($me) {
            return "' . " . $me->handleDotNotation($m[1]) . " . '";
        }, $func);
        // Conditional
        $func = preg_replace_callback("/\{\{\?(\?)?\s*([\s\S]*?)\s*\}\}/", function ($m) use ($me) {
            $elsecase = $m[1];
            $code = $m[2];
            if ($elsecase) {
                if ($code) {
                    return "';} else if (" . $me->handleDotNotation($code) . ") { $" . "out.='";
                } else {
                    return "';} else { $" . "out.='";
                }
            } else {
                if ($code) {
                    return "'; if (" . $me->handleDotNotation($code) . ") { $" . "out.='";
                } else {
                    return "';} $" . "out.='";
                }
            }
        }, $func);
        // Iterate
        $func = preg_replace_callback("/\{\{~\s*(?:\}\}|([\s\S]+?)\s*\:\s*([\w$]+)\s*(?:\:\s*([\w$]+))?\s*\}\})/", function ($m) use ($me) {
            if (count($m) > 1) {
                $iterate = $m[1];
                $vname = $m[2];
                $iname = $m[3];
                $iterate = $me->handleDotNotation($iterate);
                return "'; for (\$$iname = 0; \$$iname < count($iterate); \$$iname++) { \$$vname = $iterate [\$$iname]; \$out.='";
            } else {
                return "';} $" . "out.='";
            }
        }, $func);
        $func = '$out = \'' . $func . '\'; return $out;';

        $this->functionBody = $func;

        return function ($it) use ($func, $replace) {
            try {
                return str_replace($replace["encode"], $replace["unsafe"], eval($func));
            } catch (\Throwable $e) {
                return $e->getMessage() . ' in ' . $func;
            }
        };
    }

    /**
     * Summary of execute
     * @param mixed $data
     * @return mixed
     */
    public function execute($data)
    {
        try {
            return ($this->functionCode)($data);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
