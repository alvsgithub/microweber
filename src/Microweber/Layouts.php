<?php
namespace Microweber;
if (!defined("MW_DB_TABLE_MODULES")) {
    define('MW_DB_TABLE_MODULES', MW_TABLE_PREFIX . 'modules');
}

if (!defined("MW_DB_TABLE_ELEMENTS")) {
    define('MW_DB_TABLE_ELEMENTS', MW_TABLE_PREFIX . 'elements');
}

if (!defined("MW_DB_TABLE_MODULE_TEMPLATES")) {
    define('MW_DB_TABLE_MODULE_TEMPLATES', MW_TABLE_PREFIX . 'module_templates');
}



class Layouts {

    public $app;

    function __construct($app=null)
    {
       
        if (!is_object($this->app)) {

            if (is_object($app)) {
                $this->app = $app;
            } else {
                $this->app = mw('application');
            }

        }


    }

    /**
     * Lists the layout files from a given directory
     *
     * You can use this function to get layouts from various folders in your web server.
     * It returns array of layouts with desctption, icon, etc
     *
     * This function caches the result in the 'templates' cache group
     *
     * @param bool|array|string $options
     * @return array|mixed
     *
     * @params $options['path'] if set i will look for layouts in this folder
     * @params $options['get_dynamic_layouts'] if set this function will scan for templates for the 'layout' module in all templates folders
     *
     *
     *
     *
     *
     */
    public function scan($options = false)
    {
        $options = parse_params($options);
        if (!isset($options['path'])) {
            if (isset($options['site_template']) and (strtolower($options['site_template']) != 'default') and (trim($options['site_template']) != '')) {
                $tmpl = trim($options['site_template']);
                $check_dir = MW_TEMPLATES_DIR . '' . $tmpl;
                if (is_dir($check_dir)) {
                    $the_active_site_template = $tmpl;
                } else {
                    $the_active_site_template = mw('option')->get('curent_template');
                }
            } else {
                $the_active_site_template = mw('option')->get('curent_template');
            }
            $path = normalize_path(MW_TEMPLATES_DIR . $the_active_site_template);
        } else {
            $path = $options['path'];
        }
        if (isset($the_active_site_template) and trim($the_active_site_template) != 'default') {
            if (!isset($path) or $path == false or (!strstr($path, DEFAULT_TEMPLATE_DIR))) {
                $use_default_layouts = $path . 'use_default_layouts.php';
                if (is_file($use_default_layouts)) {
                    $path = DEFAULT_TEMPLATE_DIR;
                }
            }

        }

        if (!isset($options['no_cache'])) {
            $args = func_get_args();
            $function_cache_id = '';
            foreach ($args as $k => $v) {

                $function_cache_id = $function_cache_id . serialize($k) . serialize($v);
            }

            $cache_id = $function_cache_id = __FUNCTION__ . crc32($path . $function_cache_id);

            $cache_group = 'templates';

            $cache_content = $this->app->cache->get($cache_id, $cache_group, 'files');

            if (($cache_content) != false) {

                return $cache_content;
            }
        }

        $glob_patern = "*.php";
        $template_dirs = array();
        if (isset($options['get_dynamic_layouts'])) {

            $_dirs = glob(MW_TEMPLATES_DIR . '*', GLOB_ONLYDIR);
            $dir = array();
            foreach ($_dirs as $item) {
                $possible_dir = $item . DS . 'modules' . DS . 'layout' . DS;

                if (is_dir($possible_dir)) {
                    $template_dirs[] = $item;
                    $dir2 = mw('Microweber\Utils\Files')->rglob($possible_dir . '*.php', 0);
                    // d($dir2);
                    if (!empty($dir2)) {
                        foreach ($dir2 as $dir_glob) {
                            $dir[] = $dir_glob;
                        }
                    }
                }
            }


            // d($dir);
            //  return $dir;
        }


        if (!isset($options['get_dynamic_layouts'])) {
            if (!isset($options['filename'])) {
                $dir = mw('Microweber\Utils\Files')->rglob($glob_patern, 0, $path);
            } else {
                $dir = array();
                $dir[] = $options['filename'];
            }
        } else {

        }


        $configs = array();
        if (!empty($dir)) {

            foreach ($dir as $filename) {
                $skip = false;
                if (!isset($options['get_dynamic_layouts'])) {
                    if (!isset($options['for_modules'])) {
                        if (strstr($filename, 'modules' . DS)) {
                            $skip = true;
                        }
                    } else {
                        if (!strstr($filename, 'modules' . DS)) {
                            $skip = true;
                        }
                    }
                }
                if ($skip == false) {
                    $fin = file_get_contents($filename);
                    $here_dir = dirname($filename) . DS;
                    $to_return_temp = array();
                    if (preg_match('/type:.+/', $fin, $regs)) {

                        $result = $regs[0];
                        $result = str_ireplace('type:', '', $result);
                        $to_return_temp['type'] = trim($result);
                        $to_return_temp['directory'] = $here_dir;

                        $templ_dir = str_replace(MW_TEMPLATES_DIR, '', $here_dir);
                        if ($templ_dir != '') {
                            $templ_dir = explode(DS, $templ_dir);
                            //d($templ_dir);
                            if (isset($templ_dir[0])) {
                                $to_return_temp['template_dir'] = $templ_dir[0];

                            }

                        }


                        if (strtolower($to_return_temp['type']) == 'layout') {

                            $to_return_temp['directory'] = $here_dir;
                            if (preg_match('/is_shop:.+/', $fin, $regs)) {
                                $result = $regs[0];
                                $result = str_ireplace('is_shop:', '', $result);
                                $to_return_temp['is_shop'] = trim($result);
                            }

                            if (preg_match('/name:.+/', $fin, $regs)) {
                                $result = $regs[0];
                                $result = str_ireplace('name:', '', $result);
                                $to_return_temp['name'] = trim($result);
                            }


                            if (preg_match('/version:.+/', $fin, $regs)) {
                                $result = $regs[0];
                                $result = str_ireplace('version:', '', $result);
                                $to_return_temp['version'] = trim($result);
                            }


                            if (preg_match('/icon:.+/', $fin, $regs)) {
                                $result = $regs[0];
                                $result = str_ireplace('icon:', '', $result);
                                $to_return_temp['icon'] = trim($result);

                                $possible = $here_dir . $to_return_temp['icon'];
                                if (is_file($possible)) {
                                    $to_return_temp['icon'] = mw('url')->link_to_file($possible);
                                } else {
                                    unset($to_return_temp['icon']);
                                }
                            }

                            if (preg_match('/image:.+/', $fin, $regs)) {
                                $result = $regs[0];
                                $result = str_ireplace('image:', '', $result);
                                $to_return_temp['image'] = trim($result);
                                $possible = $here_dir . $to_return_temp['image'];
                                if (is_file($possible)) {
                                    $to_return_temp['image'] = mw('url')->link_to_file($possible);
                                } else {
                                    unset($to_return_temp['image']);
                                }

                            }

                            if (preg_match('/description:.+/', $fin, $regs)) {
                                $result = $regs[0];
                                $result = str_ireplace('description:', '', $result);
                                $to_return_temp['description'] = trim($result);
                            }

                            if (preg_match('/content_type:.+/', $fin, $regs)) {
                                $result = $regs[0];
                                $result = str_ireplace('content_type:', '', $result);
                                $to_return_temp['content_type'] = trim($result);
                            }

                            $layout_file = str_replace($path, '', $filename);

                            if(isset($template_dirs) and !empty($template_dirs)){
                                foreach($template_dirs as $template_dir){
                                    $layout_file = str_replace($template_dir, '', $layout_file);

                                }
                            }

                            //   $layout_file = str_replace(MW_TEMPLATES_DIR, '', $layout_file);


                            // d(  $layout_file);
                            $layout_file = str_replace(DS, '/', $layout_file);
                            $to_return_temp['layout_file'] = $layout_file;
                            $to_return_temp['filename'] = $filename;
                            $screen = str_ireplace('.php', '.png', $filename);
                            if (is_file($screen)) {
                                $to_return_temp['screenshot'] = $screen;
                            }

                            $configs[] = $to_return_temp;
                        }
                    }
                }
            }

            if (!empty($configs)) {
                if (!isset($options['no_cache'])) {
                    $this->app->cache->save($configs, $function_cache_id, $cache_group, 'files');
                }
                return $configs;
            } else {
                //$this->app->cache->save(false, $function_cache_id, $cache_group);
            }
        } else {
            //$this->app->cache->save(false, $function_cache_id, $cache_group);
        }
    }

   public function get($params = false)
    {

        $table = MW_DB_TABLE_ELEMENTS;
        if (is_string($params)) {
            $params = parse_str($params, $params2);
            $params = $options = $params2;
        }
        $params['table'] = $table;
        $params['group_by'] = 'module';
        $params['orderby'] = 'position asc';

        $params['cache_group'] = 'elements/global';
        if (isset($params['id'])) {
            $params['limit'] = 1;
        } else {
            $params['limit'] = 1000;
        }

        if (!isset($params['ui'])) {
            //   $params['ui'] = 1;
        }

        $s = $this->app->db->get($params);
        // d($params); d( $s);
        return $s;
    }
    public function get_link($options = false)
    {
        $args = func_get_args();
        $function_cache_id = '';
        foreach ($args as $k => $v) {

            $function_cache_id = $function_cache_id . serialize($k) . serialize($v);
        }

        $cache_id = __FUNCTION__ . crc32($function_cache_id);
        //get cache from memory
        $mem = mw_var($cache_id);
        if ($mem != false) {

            return $mem;
        }

        $options = parse_params($options);
        $fn = false;

        if (isset($options[0])) {
            $fn = $options[0];
        } elseif (is_array($options)) {
            $val = current($options);
            $fn = key($options);
        }

        $page_url_segment_1 = mw('url')->segment(0);
        $td = MW_TEMPLATES_DIR . $page_url_segment_1;
        $td_base = $td;

        $page_url_segment_2 = mw('url')->segment(1);
        $directly_to_file = false;
        $page_url_segment_3 = mw('url')->segment();

        if (!is_dir($td_base)) {
            array_shift($page_url_segment_3);
            //$page_url_segment_1 =	$the_active_site_template = mw('option')->get('curent_template');
            //$td_base = MW_TEMPLATES_DIR .  $the_active_site_template.DS;
        } else {

        }
        if (empty($page_url_segment_3)) {
            $page_url_segment_str = '';
        } else {
            $page_url_segment_str = $page_url_segment_3[0];
        }
        //$page_url_segment_str = implode('/', $page_url_segment_3);
        $fn = mw_site_url($page_url_segment_str . '/' . $fn);
        //d($page_url_segment_3);

        //set cache in memory
        mw_var($cache_id, $fn);

        return $fn;
    }
    public function save($data_to_save)
    {

        if (is_admin() == false) {
            return false;
        }
        if (isset($data_to_save['is_element']) and $data_to_save['is_element'] == true) {
            exit(__FILE__ . __LINE__ . d($data_to_save));
        }

        $table = MW_TABLE_PREFIX . 'elements';
        $save = false;
        // d($table);
        //d($data_to_save);

        if (!empty($data_to_save)) {
            $s = $data_to_save;
            // $s["module_name"] = $data_to_save["name"];
            // $s["module_name"] = $data_to_save["name"];
            if (!isset($s["parent_id"])) {
                $s["parent_id"] = 0;
            }
            if (!isset($s["id"]) and isset($s["module"])) {
                $s["module"] = $data_to_save["module"];
                if (!isset($s["module_id"])) {
                    $save = self::get('limit=1&module=' . $s["module"]);
                    if ($save != false and isset($save[0]) and is_array($save[0])) {
                        $s["id"] = $save[0]["id"];
                        $save = $this->app->db->save($table, $s);
                    } else {
                        $save = $this->app->db->save($table, $s);
                    }
                }
            } else {
                $save = $this->app->db->save($table, $s);
            }

            //
            // d($s);
        }

        if ($save != false) {

            $this->app->cache->delete('elements' . DIRECTORY_SEPARATOR . '');
            $this->app->cache->delete('elements' . DIRECTORY_SEPARATOR . 'global');
        }
        return $save;
    }




    public function delete_all()
    {
        if (is_admin() == false) {
            return false;
        } else {

            $table = MW_TABLE_PREFIX . 'elements';

            $db_categories = MW_TABLE_PREFIX . 'categories';
            $db_categories_items = MW_TABLE_PREFIX . 'categories_items';

            $q = "delete from $table ";
            //   d($q);
            $this->app->db->q($q);

            $q = "delete from $db_categories where rel='elements' and data_type='category' ";
            // d($q);
            $this->app->db->q($q);

            $q = "delete from $db_categories_items where rel='elements' and data_type='category_item' ";
            // d($q);
            $this->app->db->q($q);
            $this->app->cache->delete('categories' . DIRECTORY_SEPARATOR . '');
            $this->app->cache->delete('categories_items' . DIRECTORY_SEPARATOR . '');

            $this->app->cache->delete('elements' . DIRECTORY_SEPARATOR . '');
        }
    }

}