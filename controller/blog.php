<?php

namespace Goteo\Controller {

    use Goteo\Core\View,
        Goteo\Model;

    class Blog extends \Goteo\Core\Controller {
        
        public function index ($post = null) {

            if (!empty($post)) {
                $show = 'post';
            } else {
                $show = 'list';
            }

            // sacamos su blog
            $blog = Model\Blog::get(\GOTEO_NODE, 'node');

            if (isset($_GET['tag'])) {
                $tag = Model\Blog\Post\Tag::get($_GET['tag']);
                if (!empty($tag->id)) {
                    $blog->posts = Model\Blog\Post::getList($blog->id, $tag->id);
                }
            }

            // segun eso montamos la vista
            return new View(
                'view/blog/index.html.php',
                array(
                    'blog' => $blog,
                    'show' => $show,
                    'tag'  => $tag,
                    'post' => $post,
                    'node' => \GOTEO_NODE
                )
             );

        }
        
    }
    
}