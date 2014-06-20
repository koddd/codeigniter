<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class queries extends CI_Model {

    var $title   = '';
    var $content = '';
    var $date    = '';

    function __construct()
    {
        parent::__construct();
    }

    function is_blog_entry($entryid = '')
    {
        if(!empty($entryid)) {
            // $query = $this->db->query("SELECT * FROM `blog_post` WHERE `id` = '".$entryid."'");
            // return ($query->num_rows() == 1) ? TRUE : FALSE;

            $user_data = $this->blog->readdata(array("id" => $entryid));
            return (count($user_data) == 1) ? $user_data : FALSE;
        } else {
            return FALSE;
        }
    }

    function is_blog_comments_forapprove($entryid = '')
    {
        if(!empty($entryid)) {
            $query = $this->db->query("SELECT `id` FROM `blog_comment` WHERE `post_id` = '".$entryid."' AND `status` = 1");
            return $query->num_rows();
        } else {
            return FALSE;
        }
    }

    function blog_ctoken($token = '')
    {
        // return comment by token
        if(isset($token) && !empty($token) && preg_match('/^\w{32}$/', $token)) {
            $query = $this->db->query("SELECT *, (SELECT `title` FROM `blog_post` WHERE blog_post.id = blog_comment.post_id) AS `blog_entry_title` FROM `blog_comment` WHERE `token` = '".$token."'");
            return ($query->num_rows() > 0) ? $query->row() : FALSE;
        }
    }

    function blog_comment_action($entryid = '', $action = 1) {
        if(isset($entryid)) {
            $query = $this->db->query("UPDATE `blog_comment` SET `status` = '".$action."' WHERE `id` = '".$entryid."'");
        }
    }

    function get_blog_entry($comments_data)
    {
        $this->limit_start = isset($comments_data['limit']['start']) ? $comments_data['limit']['start'] : $this->config->item('blog_comments_limit_start');
        $this->limit_end   = isset($comments_data['limit']['end'])   ? $comments_data['limit']['end']   : $this->config->item('blog_comments_limit_end');
        $this->entryid     = $comments_data['entryid'];

        if(!empty($this->entryid)) {
            $query = $this->db->query("SELECT *, (SELECT `nickname` FROM `user` WHERE user.id = id_autor) AS `nickname`, (SELECT COUNT(*) FROM `blog_comment` WHERE blog_comment.post_id = blog_post.id AND blog_comment.status <= 1 LIMIT ".$this->limit_start.", ".$this->limit_end.") AS `comments`, UNIX_TIMESTAMP(`date`) AS `date_timestamp` FROM `blog_post` WHERE `id` = '".$this->entryid."' ");
            return $query->row();
        } else {
            return FALSE;
        }
    }

    function comment_reply($comment_id = '', $entryid) {
        $user_data = $this->users->user_data;

        $comment_rdata = array(
            'username' => $this->session->userdata('username'),
            'signature' => isset($user_data->signature) ? $user_data->signature: false,
            'comment_url' => base_url($this->router->fetch_class().'/'.$entryid.'/'),
            'comment_id' => $comment_id,
            'entry_id' => $entryid
        );
        return $this->load->view('chanks/blog_comment_reply_form', $comment_rdata, TRUE);
    }

    function get_blog_tags($type = '', $entry = '')
    {
        if(!empty($entry)) {
            if(isset($entry->{$type})) {
                $result = array();

                foreach($entry->{$type} as $item) {
                    $result[] = anchor('/'.$this->load->get_var("controller_name").'/'.$type.'/'.urlencode($item), $item);
                }

                return join(' ', $result);
            }
        }

        return FALSE;
        /*
        if(!empty($entryid)) {
            $query = $this->db->query("SELECT `id`, `tag`, COUNT(tag) AS tags_count  FROM `blog_tags` GROUP BY `tag` HAVING `id` = '".$entryid."' ORDER BY tags_count DESC");
            return $query->result();
        } else {
            return FALSE;
        }
        */
    }

    function get_allblog_tags()
    {
        // $query = $this->db->query("SELECT `tag`, COUNT(tag) AS tags_count  FROM `blog_tags` GROUP BY `tag` ORDER BY tags_count DESC");
        // return $query->result();

        return FALSE;
    }

    function blog_is_tags($tag_string = '')
    {
        if(isset($tag_string) && !empty($tag_string) && preg_match('/^\w{2,32}$/', $tag_string)) {
            $tag_string = preg_replace('/_/', ' ', $tag_string);
            /*
            $query = $this->db->query("SELECT * FROM `blog_tags` WHERE `tag` = '".$tag_string."'");
            if($query->num_rows() > 0) {
                $query = $this->db->query("SELECT `id`, `id_autor`, (SELECT `nickname` FROM `user` WHERE user.id = id_autor) AS `nickname`, (SELECT COUNT(*) FROM `blog_comment` WHERE blog_comment.post_id = blog_post.id AND blog_comment.status <= 1 ) AS `comments`, `title`, `content`, `date`, UNIX_TIMESTAMP(`date`) AS `date_timestamp`, `date_upd`, `status`, `tags` FROM `blog_post`, (SELECT `id` AS ident FROM `blog_tags` WHERE `tag`='".$tag_string."') AS `tid` WHERE blog_post.id = tid.ident");
                return $query->result();
            } else {
                return FALSE;
            }
            */
        }

        return FALSE;
    }


    function get_blog_comments($comments_data) // , $replyid = ''
    {
        $this->limit_start = isset($comments_data['limit']['start']) ? $comments_data['limit']['start'] : $this->config->item('blog_comments_limit_start');
        $this->limit_end   = isset($comments_data['limit']['end'])   ? $comments_data['limit']['end']   : $this->config->item('blog_comments_limit_end');
        $this->entryid     = $comments_data['entryid'];

        if(!empty($this->entryid)) {

            $query_string = "SELECT blog_comment.id, blog_comment.id AS `comment_id`, blog_comment.post_id, blog_comment.replyto_id, blog_comment.status, blog_comment.name, blog_comment.email, blog_comment.content, UNIX_TIMESTAMP(blog_comment.date) AS `date_timestamp`, DATE_FORMAT(`date`, '%M %D, %Y') AS `date_formatted`, (SELECT COUNT(`nickname`) FROM `user` WHERE `nickname` LIKE blog_comment.name AND `status` = 1) AS `is_user`, IF((SELECT COUNT(`nickname`) FROM `user` WHERE `nickname` LIKE blog_comment.name AND `status` = 1) = 1, (SELECT CONCAT(user.firstname, ' ', user.lastname) FROM `user` WHERE `nickname` LIKE blog_comment.name AND `status` = 1), 'NULL') AS `line`, @rank:=@rank+1 AS `rank`, IF(MOD(@rank, 2) = 1, 'odd', 'even') as `order` FROM `blog_comment` WHERE blog_comment.post_id = '".$this->entryid."'";
            /*
            if($replyid != '') {
                $query_string .= "AND `replyto_id` = '".$replyid."'";
            } else {
                $query_string .= "AND `replyto_id` IS NULL";
            }
            */
            $query_string .= " AND `status` <= 1 ORDER BY id ASC LIMIT ".$this->limit_start.", ".$this->limit_end;
            $this->db->query("SET @rank=0");
            $query = $this->db->query($query_string);

            if($query->num_rows() > 0) {
                return $query->result();
            }

        }

        return FALSE;
    }


    function get_blog_comments_p($comments_data) {
        $result = array();

        $this->entryid = $comments_data['entryid'];
        $comments_data = $this->blog->readcomments($this->entryid);

        if($comments_data && count($comments_data) > 0) {
            foreach($comments_data as $item) {
                $item->comment_id = $item->id; //
                $item->date_formatted = date('M d, Y', $item->date); //
                $item->is_user = $this->users->isuser($item->name) ? 0 : 1;
                $result[] = $item;
            }

            return $this->make_comments_tree($result);
        }

        return FALSE;
    }

    function make_comments_tree($items) {
        $childs = array();

        foreach($items as $item) {
            if(is_numeric($item->replyto_id)) {
                $childs[$item->replyto_id][] = $item;
            } else {
                $childs['result'][] = $item;
            }
        }

        foreach($items as $item) if (isset($childs[$item->id])) {
            $item->replyes = $childs[$item->id];
        }

        return $childs['result'];
    }

    /*
    function blog_comments_token() {
        $query_string = "SELECT `id`, `token` FROM `blog_comment`";
        $query = $this->db->query($query_string);

        foreach($query->result() as $row) {
            $new_token = random_string('unique');
            echo $new_token.'<br>';
            $query_string = "UPDATE `blog_comment` SET `token`='".$new_token."' WHERE `id`='".$row->id."' ";
            $this->db->query($query_string);
        }
    }
    */

    function get_blog_comments_cycle($result, $from = '', $to = '') {
        if(isset($result) && !empty($result)) {
            $tree = array();
            if(gettype($from) == 'object') {
                foreach($to as $k=>$res) {
                    if($from->replyto_id == $res->id) {
                        $z = $k;
                        $tree[] = $from;
                    }
                }

                if(isset($z)) {
                    if(gettype($to[$z]->replyes) == 'boolean') {
                        $to[$z]->replyes = (object) $tree;
                    } else {
                        $to[$z]->replyes = (object) array_merge((array) $to[$z]->replyes, (array) $tree);
                    }
                    unset($z);
                }

                return $to;
            } else {
                foreach($result as $k=>$res) {
                    if(empty($res->replyto_id)) {
                        $res->replyes = false;
                        $tree[$k] = $res;
                    } else {
                        $tree = $this->get_blog_comments_cycle($result, $res, $tree);
                    }
                }

                return (object) $tree;
            }
        }
    }

    function get_blog_comments_tree($entryid) {
        $tree = array();
        $result = $this->get_blog_comments($entryid);
        $return = $this->get_blog_comments_cycle($this->get_blog_comments($entryid));
    }

    function get_blog_commentsf($entryid = '') {
        if(!empty($entryid)) {
            $query_string = "SELECT `id` FROM `blog_comment` WHERE `post_id` = '".$entryid."' AND `status` <= 1";
            $query = $this->db->query($query_string);

            if($query->num_rows() > 0) {
                return $query->result();
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    // array of blog comments
    // for differennce of list id
    function get_blog_comments_in($entryid = '', $keys = '')
    {
        if(!empty($entryid)) {
            $query_string = "SELECT *, UNIX_TIMESTAMP(`date`) AS `date_timestamp`, date_format(`date`, '%M %D, %Y') AS `date_formatted` FROM `blog_comment` WHERE `post_id` = '".$entryid."' AND `id` IN (".join(', ', $keys).") AND `status` <= 1";
            $query = $this->db->query($query_string);

            if($query->num_rows() > 0) {
                return $query->result();
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    function get_blog_comment_for($commentid = '')
    {
        if(!empty($commentid)) {
            $query = $this->db->query("SELECT *, UNIX_TIMESTAMP(`date`) AS `date_timestamp`, date_format(`date`, '%M %D, %Y') AS `date_formatted` FROM `blog_comment` WHERE `id` = '".$commentid."'");

            if($query->num_rows() > 0) {
                return $query->row();
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    function get_comment_unapproved()
    {
        $query = $this->db->query("SELECT `id` FROM `blog_comment` WHERE `status` < 1");
        return $query->num_rows();
    }

    function comments_for_approve_list()
    {
        $query = $this->db->query("SELECT `id`, `post_id`, `name`, `content`, `token`, `date`, UNIX_TIMESTAMP(`date`) AS `date_timestamp`, date_format(`date`, '%M %D, %Y') AS `date_formatted`, (SELECT `title` FROM `blog_post` WHERE blog_comment.post_id = blog_post.id) AS `post_title`  FROM `blog_comment` WHERE `status` = 1 ORDER BY `date` DESC");
        if($query->num_rows() > 0) {
            return $query->result();
        }
        return FALSE;
    }

    function is_user($username = '')
    {
        if(!empty($username)) {
            $query = $this->db->query("SELECT * FROM `user` WHERE `nickname`='".$username."' ");

            return $query->num_rows();
        } else {
            return FALSE;
        }
    }

    function get_topuser_list($total = 2) {
        /*
        if(empty($username)) {
            $query = $this->db->query("SELECT `id`, `nickname`, `firstname`, `lastname`, `position`, (SELECT COUNT(*) AS `count` FROM `blog_post` WHERE blog_post.id_autor = user.id) AS `blog_posts_count` FROM `user` WHERE `status` = 1");
        } else {
            $query = $this->db->query("SELECT `id`, `nickname`, `firstname`, `lastname`, `position` FROM `user` WHERE `nickname` = '".$username."' AND `status` = 1 AND blog_post.id_autor = id");
        }
        if($query->num_rows() > 0) {
            $res = array();

            foreach($query->result() as $key => $row) {
                $obj = new stdClass();
                $obj->firstname = $row->firstname;
                $obj->lastname = $row->lastname;
                $obj->nickname = $row->nickname;
                $obj->posts = $row->blog_posts_count;
                $obj->userpic  = $this->adminkamodel->userpic($row->nickname);
                $res[$key] = $obj;
            }
            return $res;
        }
        */

        $blog_entryes = $this->blog->readdata(array("status" => 1));
        $users = array();
        foreach($blog_entryes as $entry) {
            if(isset($users[$entry->id_autor])) {
                $users[$entry->id_autor] ++;
            } else {
                $users[$entry->id_autor] = 0;
            }
        }
        arsort($users);

        $i=0;
        $result = array();
        foreach($users as $user => $rating) {
            if($i < $total) {
                $user_item = $this->users->getuserdata($user);

                $obj = new stdClass();
                $obj->firstname = $user_item->firstname;
                $obj->lastname = $user_item->lastname;
                $obj->nickname = $user_item->nickname;
                $obj->posts = count( $this->blog->readdata(array("status" => 1, "nickname" => $user)) );
                $obj->userpic  = $this->users->userpic($user);

                $result[] = $obj;
            } else {
                break;
            }
            $i++;
        }

        // echo "<pre>".print_r($result, true)."</pre>";

        return (count($result) > 0) ? $result : FALSE;


        return FALSE;
    }

    function get_team_list()
    {
        // $query = $this->db->query("SELECT `id`, `nickname`, `firstname`, `lastname`, `position` FROM `user` WHERE status = 1");
        // if($query->num_rows() > 0) {
        //     return $query->result();
        // }
        // return FALSE;

        $users = $this->users->getuserslist(array(
            "status"      => 0,
            "usergroup"   => "team"
        ));

        return $users;
    }

    function get_last_ten_entries($page = 0, $items_per_page = 50)
    {
        // $query = $this->db->query("SELECT *, (SELECT `nickname` FROM `user` WHERE user.id = id_autor) AS `nickname`, (SELECT COUNT(*) FROM `blog_comment` WHERE blog_comment.post_id = blog_post.id AND blog_comment.status <= 1) AS `comments`, UNIX_TIMESTAMP(`date`) AS `date_timestamp`, date_format(`date`, '%M %D, %Y') AS `date`, `title`, `content` FROM `blog_post` WHERE `status` = 1 ORDER BY id DESC LIMIT $page, $items_per_page");
        // return $query->result();

        $end_limit = $page+$items_per_page;

        // echo $page." => ".$end_limit."<br>\n";
        // echo count($this->blog->readdata(array("status" => 1), null))."<br>";

        return $this->blog->readdata(array("status" => 1), null, $page, $end_limit);
    }

    function get_blog_rss_entries()
    {
        // $query = $this->db->query("SELECT *, (SELECT `nickname` FROM `user` WHERE user.id = id_autor) AS `nickname`, (SELECT COUNT(*) FROM `blog_comment` WHERE blog_comment.post_id = blog_post.id AND blog_comment.status <= 1) AS `comments`, UNIX_TIMESTAMP(`date`) AS `date_timestamp`, date_format(`date`, '%M %D, %Y') AS `date`, `title`, `content` FROM `blog_post` WHERE `status` = 1 ORDER BY id DESC");
        // return $query->result();
        return $this->blog->readdata(array("status" => 1), null);
    }

    function date_rss($date)
    {
        return date('d F Y h:i:s', $date);
    }

    function get_total_entries($userid='')
    {
        /*
        $query_string = "SELECT `id` FROM `blog_post` WHERE `status` = 1";
        if(preg_match('/\d{1,}/', $userid)) {
            $query_string .= " AND `id_autor` = '".$userid."'";
        }
        $query = $this->db->query($query_string);
        return $query->num_rows();
        */

        return count( $this->blog->readdata(array("status" => 1)) );
        // return count( $this->blog->readdata(array("status" => "<=%1")) );
    }

    function insert_entry(array $user_data)
    {
        $this->id_autor = intval($user_data['id']);
        $this->title    = htmlspecialchars($_POST['title'], ENT_QUOTES); // please read the below note
        $this->content  = htmlspecialchars(strip_tags($_POST['content'], "<b><i><u><p>"), ENT_QUOTES);
        $this->date     = time();
        $this->tags     = htmlspecialchars($_POST['tags'], ENT_QUOTES);
        $this->status   = 1; // not approved for display

        $this->db->query("INSERT INTO `zepo_in`.`blog_post` (`id_autor`, `title`, `content`, `date`, `status`) VALUES ('".$this->id_autor."', '".$this->title."', '".$this->content."', NOW(), '".$this->status."')");
        $this->insert_entry_tags($this->tags, $this->db->insert_id());

        return TRUE;
    }

    function content_chank($entries) {
        if(isset($entries) && !empty($entries)) {
            $lench = $this->config->item('content_chank_length');
            $string_result = '';

            $content = preg_split('/##-- '.$entries->id.' --##/', $entries->content, -1, PREG_SPLIT_NO_EMPTY);
            if(isset($content[1])) { $string_result = $content[0]; /* $string_result = strip_tags($content[0]); */ } else {
                if(strlen($entries->content) <= $lench) {
                    $string_result = strip_tags($entries->content);
                } else {
                    $words = explode(" ", strip_tags($entries->content));
                    foreach($words as $word) {
                        if(strlen($string_result) + strlen($word) > $lench) {
                            break;
                        } else {
                            $string_result .= ' '.$word;
                        }
                    }
                }
            }

            // echo var_dump($content);

            return $string_result;
        }

        return FALSE;
    }

    function content_full($string = '') {
        return preg_replace('/##-- (\d+) --##/', '<!-- $1 -->', $string);
    }


    function content_fullength($entries) {
        if(isset($entries) && !empty($entries)) {
            $string_result = preg_replace('/<!-- '.$entries->id.' -->/', '', $entries->content);

            return $string_result;
        }

        return FALSE;
    }

/*
    function insert_comment()
    {
        if ( ! function_exists('genencryptkeys')) { $this->load->helper(array('string', 'passreminder')); }
        $crypted_string = genencryptkeys($this->date);

        $this->post_id  = intval($this->input->post('post_id'));
        $this->name     = htmlspecialchars($this->input->post('name'), ENT_QUOTES); // please read the below note
        $this->email    = htmlspecialchars($this->input->post('email'), ENT_QUOTES); // please read the below note
        $this->url      = htmlspecialchars($this->input->post('url'), ENT_QUOTES); // please read the below note
        $this->content  = ''; // htmlspecialchars(preg_replace("/([^\s]{35})/","$1 ", $this->input->post('content'), ENT_QUOTES);
        $this->status   = 1;
        $this->token    = random_string('unique');

        $this->db->query("INSERT INTO `zepo_in`.`blog_comment` (`post_id`, `name`, `email`, `content`, `date`, `status`, `token`) VALUES ('".$this->post_id."', '".$this->name."', '".$this->email."', '".$this->content."', NOW(), '".$this->status."', '".$this->token."')");
        return $this->db->insert_id();
    }
*/

    function insert_comment()
    {
        if ( ! function_exists('genencryptkeys')) { $this->load->helper(array('string', 'passreminder')); }

        $this->post_id  = intval($this->input->post('post_id'));
        $this->comment_id  = $this->input->post('comment_id') ? intval($this->input->post('comment_id')) : FALSE;

        if($this->session->userdata('logged_in')) {
            $this->name     = $this->session->userdata('signature');
            $this->email    = '';
        } else {
            $this->name     = htmlspecialchars($this->input->post('name'), ENT_QUOTES);
            $this->email    = $this->input->post('email');
        }

        $this->content  = preg_replace("/([^\s]{35})/","$1 ", $this->input->post('content'));
        $this->content  = htmlspecialchars($this->content, ENT_QUOTES);

        $this->token    = random_string('unique');

        $query_string = "INSERT INTO `blog_comment` (`post_id`, ";
        if($this->comment_id) { $query_string .= "`replyto_id`, "; }
        $query_string .= "`name`, `email`, `content`, `date`, `token`) VALUES ('".$this->post_id."', ";
        if($this->comment_id) { $query_string .= "'".$this->comment_id."', "; }
        $query_string .= "'".$this->name."', '".$this->email."', '".$this->content."', NOW(), '".$this->token."')";

        $this->db->query($query_string);
        return $this->db->insert_id();
    }

    function validate_time() {
        $query = $this->db->query("SELECT * FROM `blog_comment` WHERE `date` >= NOW() - 4");
        return $query->num_rows(); //  == 0) ? TRUE : FALSE
    }

    //
    // Переделать копипастовича на яваскрипт
    //
    function validate_copypaste($str) {
        $query = $this->db->query("SELECT `content` FROM `blog_comment` WHERE `content` LIKE '%".$str."%' LIMIT 0 , 30");
        return $query->num_rows(); // > 0) ? FALSE : TRUE;
        // $query->result()
    }


    function checkstring() {

        $this->db->query("SELECT * FROM `blog_comment` WHERE `date` >= NOW() - 4");
        return ($query->num_rows() > 0) ? $result_array : FALSE;

        // 1/ Проверить на совпадение эдентичность строки => при совпадении игнорировать
        // 2/ Проверить на повторение одного и того же символа более n-раз (~60)
        // 3/ Проверить на слова белиберду => dklsdjlkgjfdg sfddf
        // 4/ Проверить на совпадение отрывка 50% строки => если дата совпадения < 20 минут => игнорировать пост, если больше => добавить => с пометкой спам
        // 5/ => если дата совпадения < 20 минут => игнорировать пост, если больше => добавить => с пометкой спам
        // 6/ разбить на слова, подсчитать колличество слов, разбить сообщение на 5-6 отрезков, проверить совпадение на
        // 7/ а если совпадений больше 80% => игнорировать, > 50% => предположим вставили цитату, пропускаем, ставим преположительно спам,
        // 7/ 25% => ? че делать
        // 8/ из черного списка слов спама проверить на присутствие в тектсте (заполняется вручную пока что)
        // count words / if length > limit = split 'n' parts ==> check SELECT WHERE LIKE % string % database
        //
        // последнее проверять яваскриптом onsubmit() до отправки на сервер
        //
        // $words_count = strlen($this->content);
        //
        //
        // проверяю на длину текста
        // проверяю на частую отправку формы
        // проверяется еще при валидации формы
        //

        // level CHE
        // Разрешена ли отправка формы POST
        // если разрешена -> проходите
        // если не разрешена -> пишем логи
        //
        // если сильно много запросов -> на все запросы выводим страницу ошибки
        // поле -> цитадель -> все бегут  и взрывы :) -> много трафика
        //

        // level 1A
        // проверяем черный ip-список на совпадение -> постоянное хранение, временное
        //
        //
        // level 1
        // $query_check = "SELECT * FROM `blog_comment` WHERE `content` = '".$this->content."' AND `date` >= NOW()-300";
        // проводится при валидации
        // если совпадение есть и level = '0' => добавляем помечаем как спам "level 1"
        // если совпадение есть и level <= 1 => игнорируем и
        //
        // добавляем ip в черный список
        //
        //
        // level 2
        // проверяю полное совпадение, но без даты
        // почему я не могу сразу проверить на первом уровне и проанализировать результат?
        // видимо потому что не усложняем нагрузку на первый запрос, а не для красоты кода
        $query_check = "SELECT * FROM `blog_comment` WHERE `content` = '".$this->content."'";
        return ($query->num_rows() > 0) ? $result_array : FALSE;
        // если есть совпадение и level > 0 => игнорируем
        // если level > 0 => игнорируем
        // если не отмечен -> оставляем
        //
        // level 3
        // проверяю на частичное совпадение
        // вычисляю длинну текста
        // если строка текста большая
        // беру от 60-80% текста random()
        // если короткая отправляю проверять в черный список
        // если все ок в черном списке -> проверяю с белом (какой список объемнее?)
        // проверяю на совпадение SELECT LIKE %%
        // совпадение найдено -> спам,
        $query_check = "SELECT * FROM `blog_comment` WHERE `content` = '".$this->content."'";
        //
        // level 4
        //
        // проверяю на мелкое совпадение
        // вычисляю длинну текста
        // если строка текста большая
        // Разбиваю текст на n частей текста (слова) по N-слов
        // проверяю первую часть на совпадение
        // совпадение есть получаю идентификаторы список
        // высчитываю второе совпадение
        // совпадение идентификаторов
        // ...и тут я сдулся... продолжу позже
        // если короткая отправляю проверять в черный список

        $query_check = "SELECT * FROM `blog_comment` WHERE `content` = '".$this->content."'";
    }

    function getreplyidlist($post_id) {
        $query = $this->db->query("SELECT `replyto_id` FROM `blog_comment` WHERE `post_id` = '".$post_id."' AND `status` <= 1 AND `replyto_id` IS NOT NULL GROUP BY `replyto_id`");
        $result_array = array();
        // print_r($query->result());

        foreach($query->result() as $item) {
            $result_array[] = $item->replyto_id;
        }

        return ($query->num_rows() > 0) ? $result_array : FALSE;
    }

    function insert_entry_tags($tags, $insert_id) //
    {
        echo '<pre>'.print_r($tags, true).'</pre>'.$insert_id;
        $this->tags  = preg_split('/\s*,\s*/',trim($tags),-1,PREG_SPLIT_NO_EMPTY);
        // $this->post_id  = $this->db->insert_id();
        $this->post_id  = $insert_id;

        foreach($this->tags as $tag_value) {
            $tag_value = url_title($tag_value, ' ', TRUE);
            $this->db->query("INSERT INTO `zepo_in`.`blog_tags` (`id`, `tag`) VALUES ('".$this->post_id."', '".$tag_value."')");
        }
    }

    function update_blog_ajax($id, $data)
    {
        $this->db->update('blog_post', $data, array('id' => $id));
    }

    function is_commentid($id = '', $postid = '')
    {
        // if(preg_match('/^\d+$/', $id)) {
        if(preg_match('/^[0-9]{1,}$/', $id)) {
            $query = $this->db->query("SELECT * FROM `blog_comment` WHERE `id` = '".$id."' AND `post_id` = '".$postid."' AND `status` <= 1");
            return ($query->num_rows() == 1) ? TRUE : FALSE;
        } else {
            return FALSE;
        }
    }


    function update_bcomments_ajax($comment, $data)
    {
        if($this->db->update('blog_comment', $data, array('id' => $comment->id))) {

            if ($this->config->item('log_threshold') > 0)
            {
                $status_levels = $this->config->item('post_status_levels');
                log_message('info', 'Blog comment updated id = `'.$comment->id.'` set status to '.$data["status"].' = '.$status_levels[$data["status"]].' by `'.$this->session->userdata('username').'` --> '.base_url('/blog/comments/'.$comment->token.'/'));
            }
            return TRUE;
        }
    }

    function get_last_comment_id($postid, $num = '') {
        if ( ! function_exists('genencryptkeys')) { $this->load->helper(array('string', 'passreminder')); }
        $query = $this->db->query("SELECT `id` FROM `blog_comment` WHERE `status` <= 1 AND `post_id` = '".$postid."' ORDER BY `id` DESC LIMIT 1");

        if($query->num_rows() > 0) {
            if($num == '') { return genencryptkeys($query->row('id'), 'static'); } else { return $query->row('id'); }
        }
    }

    function get_lcid_checksum() {
        if(count($_GET) <= 0) return false;

        if ( ! function_exists('genencryptkeys')) { $this->load->helper(array('string', 'passreminder')); }
        // If equal values of total_comments and last_intert_id
        $tc_crypted_string = genencryptkeys($_GET['ln'], 'static');
        if(preg_match('/^[0-9]{1,}$/', $_GET['tc']) && preg_match('/^[0-9]{1,}$/', $_GET['ln']) && preg_match('/^[A-z0-9]{22}$/', $_GET['lid']) && $tc_crypted_string == $_GET['lid']) {
            return true;
        } else {
            return false;
        }
        return false;
    }

    function get_total_comments($postid) {
        $query = $this->db->query("SELECT COUNT(*) AS `counter` FROM `blog_comment` WHERE `post_id` = '".$postid."' AND `status` <= 1");
        return $query->row('counter');
    }



    function show404error()
    {
        if ( ! function_exists('base_url'))
        {
            $this->load->helper(array('url'));
        }


        if ($this->config->item('log_threshold') > 0)
        {
            log_message('error', '404 Page Not Found --> '.base_url($_SERVER["REQUEST_URI"]));
        }

        header("HTTP/1.1 404 Not Found");
        $this->load->view('error/404page');
    }

    function random_string($line) {
        if (!function_exists('random_element')) { $this->load->helper('array'); }

        $quotes = array(
            "I find that the harder I work, the more luck I seem to have. - Thomas Jefferson",
            "Don't stay in bed, unless you can make money in bed. - George Burns",
            "We didn't lose the game; we just ran out of time. - Vince Lombardi",
            "If everything seems under control, you're not going fast enough. - Mario Andretti",
            "Reality is merely an illusion, albeit a very persistent one. - Albert Einstein",
            "Chance favors the prepared mind - Louis Pasteur"
        );

        return random_element($quotes);
    }


}
