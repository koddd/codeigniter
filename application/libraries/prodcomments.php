<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CI_prodcomments {

    public function __construct($attr = array()) {
        $this->CI =& get_instance();
        foreach($attr as $key => $item) {
            $this->item->{$key} = $item;
        }

        $this->comment_path = $this->CI->config->item('products_comments_path');
    }

    function savecomment() {
        if(isset($this->blog_comment)) {
            $save_path = $this->comment_path;
            $comment_entry_data = json_encode($this->blog_comment);

            if(@file_put_contents($save_path, $comment_entry_data, LOCK_EX)) {
                unset($this->blog_comment, $blog_entry_data);
                return TRUE;
            }

            unset($comment_entry_data, $this->blog_comment);
        }
        return FALSE;
    }


    function gettotalcomments($data) {
        $path_to_file = sprintf($this->comment_path, $data->id_autor, $data->id);

        if(is_file($path_to_file)) {
            $content = file_get_contents($path_to_file);
            $entry_data = json_decode($content);

            return count($entry_data);
        }

        return 0;
    }

    function addcomment($data = array()) {
        if($this->_addcomment($data)) {
            /*
            $log_message = array(
                'message'  => 'Blog comment added',
                'blogid' => $blogid,
                'name' => $data["name"],
                'ip' => $_SERVER["REMOTE_ADDR"],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'http_referer' => getenv('HTTP_REFERER'),
            );
            */

            // log_rmessage('info', $log_message);
        }
    }

    function _addcomment($args = array()) {
        $this->readcomments();

        $id = (isset($this->blog_comment)) ? count($this->blog_comment) : 0;
        if(!$this->blog_comment) { $this->blog_comment = array(); }

        $item_prop = array(
            'id'  =>  $id,
            'prod_id' => '',
            'replyto_id' => '',
            'name' => '',
            'email' => '',
            'content' => '',
            'rating' => 0,
            'date' => time(),
            'status' => 0,
            'token' => ''
        );

        foreach($args as $key => $item) {
            $item_prop[$key] = $item;
        }

        // $this->blog_comment = array();
        $this->blog_comment[] = (object) $item_prop;

        if($this->savecomment()) {
            return TRUE;
        }

        return FALSE;
    }

    function updatecomment($data = array()) {
        $this->readcomments();

        if(func_num_args() == 2) {
            $id = func_get_arg(1);
        }

        foreach($this->blog_comment as $ikey => $item) {
            if(isset($id) && preg_match('/^\d+$/', $id)) {
                foreach($data as $key => $item) {
                    $this->blog_comment[$ikey]->{$key} = $item;
                }
            } else {
                foreach($data as $key => $item) {
                    $this->blog_comment[$ikey]->{$key} = $item;
                }
            }

            // $this->blog_comment[$ikey]->rating = rand(0, 5);
            // echo $this->blog_comment[$ikey]->rating."<br>";
        }

        if($this->savecomment()) {
            unset($this->blog_comment);
            return TRUE;
        }

        return FALSE;
    }

    function readcomments() {

        if(is_file($this->comment_path)) {
            $content = file_get_contents($this->comment_path);
            $this->blog_comment = json_decode($content);
            return $this->blog_comment;
        } else {
            return array();
        }

        return FALSE;
    }

}