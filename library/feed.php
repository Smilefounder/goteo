<?php
namespace Goteo\Library {

	use Goteo\Core\Model,
        Goteo\Model\Blog\Post,
        Goteo\Library\Text;

	/*
	 * Clase para loguear eventos
	 */
    class Feed {

        public
            $id,
            $title, // titulo entrada o nombre usuario
            $url = null, // enlace del titulo
            $image = null, // enlace del titulo
            $scope = 'admin', // ambito del evento (public, admin, private)
            $type =  'system', // tipo de evento  ($public_types , $admin_types, $private_types)
            $timeago, // el hace tanto
            $date, // fecha y hora del evento
            $html, // contenido del evento en codigo html
            $unique = false, // si es un evento unique, no lo grabamos si ya hay un evento con esa url
            $text,  // id del texto dinamico
            $params,  // (array serializado en bd) parametros para el texto dinamico
            $target_type, // tipo de objetivo del evento (user, project, call, node, etc..) normalmente project
            $target_id; // id registro del objetivo (normalmente varchar(50))

        static public $admin_types = array(
            'all' => array(
                'label' => 'Todo',
                'color' => 'light-blue'
            ),
            'admin' => array(
                'label' => 'Administrador',
                'color' => 'red'
            ),
            'user' => array(
                'label' => 'Usuario',
                'color' => 'blue'
            ),
            'project' => array(
                'label' => 'Proyecto',
                'color' => 'light-blue'
            ),
            'call' => array(
                'label' => 'Convocatoria',
                'color' => 'light-blue'
            ),
            'money' => array(
                'label' => 'Transferencias',
                'color' => 'violet'
            ),
            'system' => array(
                'label' => 'Sistema',
                'color' => 'grey'
            )
        );

        static public $public_types = array(
            'goteo' => array(
                'label' => 'Goteo'
            ),
            'projects' => array(
                'label' => 'Proyectos'
            ),
            'community' => array(
                'label' => 'Comunidad'
            )
        );

        static public $private_types = array(
            'info' => array(
                'label' => 'Información'
            ),
            'alert' => array(
                'label' => 'Alerta'
            )
        );

        static public $color = array(
            'user' => 'blue',
            'project' => 'light-blue',
            'call' => 'light-blue',
            'blog' => 'grey',
            'news' => 'grey',
            'money' => 'violet',
            'drop' => 'violet',
            'relevant' => 'red',
            'comment' => 'green',
            'update-comment' => 'grey',
            'message' => 'green',
            'system' => 'grey',
            'update' => 'grey'
        );

        static public $page = array(
            'user' => '/user/profile/',
            'project' => '/project/',
            'call' => '/call/',
            'drop' => SITE_URL,
            'blog' => '/blog/',
            'news' => '/news/',
            'relevant' => '',
            'comment' => '/blog/',
            'update-comment' => '/project/',
            'message' => '/project/',
            'system' => '/admin/',
            'update' => '/project/'
        );

        /**
         * Metodo que rellena instancia
         * No usamos el __construct para no joder el fetch_CLASS
         */
        public function populate($title, $url, $html, $image = null) {
            $this->title = $title;
            $this->url = $url;
            $this->html = $html;
            $this->image = $image;
        }

        /**
         * Metodo que establece el elemento al que afecta el evento
         *
         * Sufridor del evento: tipo (tabla) & id registro
         *
         * @param $id string normalmente varchar(50)
         * @param $type string (project, user, node, call, etc...)
         */
        public function setTarget ($id, $type = 'project') {
            $this->target_id = $id;
            $this->target_type = $type;
        }

        public function doAdmin ($type = 'system') {
            $this->doEvent('admin', $type);
        }

        public function doPublic ($type = 'goteo') {
            $this->doEvent('public', $type);
        }

        public function doPrivate ($type = 'info') {
            $this->doEvent('private', $type);
        }

        private function doEvent ($scope = 'admin', $type = 'system') {
            $this->scope = $scope;
            $this->type = $type;
            $this->add();
        }

        /**
		 *  Metodo para sacar los eventos
         *
         * @param string $type  tipo de evento (public: columnas goteo, proyectos, comunidad;  admin: categorias de filtro)
         * @param string $scope ambito de eventos (public | admin)
         * @return array list of items
		 */
		public static function getAll($type = 'all', $scope = 'public', $limit = '99') {

            $list = array();

            try {
                $values = array(':scope' => $scope);

                $sqlType = '';
                if ($type != 'all') {
                    $sqlType = " AND feed.type = :type";
                    $values[':type'] = $type;
                }

                $sql = "SELECT
                            feed.id as id,
                            feed.title as title,
                            feed.url as url,
                            feed.image as image,
                            DATE_FORMAT(feed.datetime, '%H:%i %d|%m|%Y') as date,
                            feed.datetime as timer,
                            feed.html as html
                        FROM feed
                        WHERE feed.scope = :scope $sqlType
                        ORDER BY datetime DESC
                        LIMIT $limit
                        ";

                $query = Model::query($sql, $values);
                foreach ($query->fetchAll(\PDO::FETCH_CLASS, __CLASS__) as $item) {

                    // si es la columan goteo, vamos a cambiar el html por el del post traducido
                    if ($type == 'goteo') {
                        // primero sacamos la id del post de la url
                        $matches = array();
                        
                        \preg_match('(\d+)', $item->url, $matches);
                        if (!empty($matches[0])) {
                            //  luego hacemos get del post
                            $post = Post::get($matches[0], LANG);

                            if ($post->owner != 'node-goteo') {
                                $the_node = \explode('-', $post->owner);
                                if (!\Goteo\Core\NodeSys::isActive($the_node[1])) {
                                    continue;
                                }
                            }

                            // y substituimos el $item->html por el $post->html
                            $item->html = Text::recorta($post->text, 250);
                            $item->title = $post->title;
                            $item->image = $post->image->id;

                            // arreglo la fecha de publicación
                            $parts = explode(' ', $item->timer);
                            $item->timer = $post->date . ' ' . $parts[1];
                        }
                    }

                    //hace tanto
                    $item->timeago = self::time_ago($item->timer);


                    $list[] = $item;
                }
                return $list;
            } catch (\PDOException $e) {
                throw new Exception('FATAL ERROR SQL: ' . $e->getMessage() . "<br />$sql<br /><pre>" . print_r($values, 1) . "</pre>");
            }
		}

        /**
		 * Metodo para sacar los eventos relacionados con un usuario
         *
         * @param string $id id del usuario
         * @param string $filter  para tipos de eventos que queremos obtener
         * @return array list of items (como getAll)
		 */
		public static function getUserItems($id, $filter = 'private') {

            $list = array();

            try {
                $values = array();

                $wheres = array();
                if (!empty($filter)) {
                    switch ($filter) {
                        case 'private':
                            // eventos que afecten al usuario
                            $wheres[] = "feed.target_type = 'user'";
                            $wheres[] = "feed.target_id = :target_id";
                            $values[':target_id'] = $id;
                            break;
                        case 'supported':
                            // eventos del proyectos que cofinancio (o he intentado cofinanciar)
                            $wheres[] = "feed.target_type = 'project'";
                            $wheres[] = "feed.target_id IN (
                                SELECT DISTINCT(invest.project) FROM invest WHERE invest.user  = :id
                                )";
                            $values[':id'] = $id;
                            break;
                        case 'comented':
                            // eventos de proyectos en los que comento pero que no cofinancio
                            $wheres[] = "feed.target_type = 'project'";
                            $wheres[] = "( feed.target_id IN (
                                SELECT DISTINCT(message.project) FROM message WHERE message.user  = :id
                                ) OR feed.target_id IN (
                                SELECT DISTINCT(blog.owner)
                                FROM comment
                                INNER JOIN post
                                    ON post.id = comment.post
                                INNER JOIN blog
                                    ON blog.id = post.blog
                                    AND blog.type = 'project'
                                WHERE comment.user  = :id
                                )
                            )";
                            $wheres[] = "feed.target_id NOT IN (
                                SELECT DISTINCT(invest.project) FROM invest WHERE invest.user  = :id
                                )";
                            $values[':id'] = $id;
                            break;
                    }
                }

                $sql = "SELECT
                            feed.id as id,
                            feed.title as title,
                            feed.url as url,
                            feed.image as image,
                            DATE_FORMAT(feed.datetime, '%H:%i %d|%m|%Y') as date,
                            feed.datetime as timer,
                            feed.html as html
                        FROM feed
                        WHERE " . implode(' AND ', $wheres) . "
                        ORDER BY datetime DESC
                        LIMIT 99
                        ";

                $query = Model::query($sql, $values);
                foreach ($query->fetchAll(\PDO::FETCH_CLASS, __CLASS__) as $item) {

                    //hace tanto
                    $item->timeago = self::time_ago($item->timer);
                    
                    $list[] = $item;
                }
                return $list;
            } catch (\PDOException $e) {
                return array();
                @\mail('goteo_fail@doukeshi.org', 'ERROR SQL en Feed::getItems', 'FATAL ERROR SQL: ' . $e->getMessage() . "<br />$sql<br /><pre>" . print_r($values, 1) . "</pre>");
            }
		}

		/**
		 *  Metodo para grabar eventos
         *
         *  Los datos del evento estan en el objeto
         *
         *
         * @param array $errors
         *
         * @access public
         * @return boolean true | false   as success
         *
		 */
		public function add() {

            if (empty($this->html)) {
                @mail('goteo_fail@doukeshi.org',
                    'Evento feed sin html: ' . SITE_URL,
                    "Feed sin contenido html<hr /><pre>" . print_r($this, 1) . "</pre>");
                return false;
            }

            if ($this->scope == 'public' && $_SESSION['user']->id == 'doukeshi') {
                return false;
            }


            // primero, verificar si es unique, no duplicarlo
            if ($this->unique === true) {
                $query = Model::query("SELECT id FROM feed WHERE url = :url AND scope = :scope AND type = :type",
                    array(
                    ':url' => $this->url,
                    ':scope' => $this->scope,
                    ':type' => $this->type
                ));
                if ($query->fetchColumn(0) != false) {
                    return true;
                }
            }

  			try {
                $values = array(
                    ':title' => $this->title,
                    ':url' => $this->url,
                    ':image' => $this->image,
                    ':scope' => $this->scope,
                    ':type' => $this->type,
                    ':html' => $this->html,
                    ':target_type' => $this->target_type,
                    ':target_id' => $this->target_id
                );

				$sql = "INSERT INTO feed
                            (id, title, url, scope, type, html, image, target_type, target_id)
                        VALUES
                            ('', :title, :url, :scope, :type, :html, :image, :target_type, :target_id)
                        ";
				if (Model::query($sql, $values)) {
                    return true;
                } else {
                    @mail('goteo_fail@doukeshi.org',
                        'Fallo al hacer evento feed: ' . SITE_URL,
                        "Ha fallado Feed<br /> {$sql} con <pre>" . print_r($values, 1) . "</pre><hr /><pre>" . print_r($this, 1) . "</pre>");
                    return false;
                }
                
			} catch(\PDOException $e) {
                    @mail('goteo_fail@doukeshi.org',
                        'PDO Exception evento feed: ' . SITE_URL,
                        "Ha fallado Feed PDO Exception<br /> {$sql} con " . $e->getMessage() . "<hr /><pre>" . print_r($this, 1) . "</pre>");
                return false;
			}

		}
        
        /**
         * Metodo para transformar un TIMESTAMP en un "hace tanto"
         * 
         * Los periodos vienen de un texto tipo singular-plural_sg-pl_id-sg-pl_...
         * en mismo orden y cantidad que los per_id
         */
        public static function time_ago($date,$granularity=1) {

            $per_id = array('sec', 'min', 'hour', 'day', 'week', 'month', 'year', 'dec');

            $per_txt = array();
            foreach (\explode('_', Text::get('feed-timeago-periods')) as $key=>$grptxt) {
                $per_txt[$per_id[$key]] = \explode('-', $grptxt);
            }

            $justnow = Text::get('feed-timeago-justnow');

            $retval = '';
            $date = strtotime($date);
            $ahora = time();
            $difference = $ahora - $date;
            $periods = array('dec' => 315360000,
                'year' => 31536000,
                'month' => 2628000,
                'week' => 604800,
                'day' => 86400,
                'hour' => 3600,
                'min' => 60,
                'sec' => 1);

            foreach ($periods as $key => $value) {
                if ($difference >= $value) {
                    $time = floor($difference/$value);
                    $difference %= $value;
                    $retval .= ($retval ? ' ' : '').$time.' ';
                    $retval .= (($time > 1) ? $per_txt[$key][1] : $per_txt[$key][0]);
                    $granularity--;
                }
                if ($granularity == '0') { break; }
            }

            return empty($retval) ? $justnow : $retval;
        }

        /**
         *  Genera codigo html para enlace o texto dentro de feed
         *
         */
        public static function item ($type = 'system', $label = 'label', $id = null) {

            // si llega id es un enlace
            if (isset($id)) {
                return '<a href="'.self::$page[$type].$id.'" class="'.self::$color[$type].'" target="_blank">'.$label.'</a>';
            } else {
                return '<span class="'.self::$color[$type].'">'.$label.'</span>';
            }


        }

        /**
         *  Genera codigo html para feed público
         *
         *  segun tenga imagen, ebnlace, titulo, tipo de enlace
         *
         */
        public static function subItem ($item) {

            $pub_timeago = Text::get('feed-timeago-published', $item->timeago);

            $content = '<div class="subitem">';

           // si enlace -> título como texto del enlace
           if (!empty($item->url)) {
                // si imagen -> segun enlace:
                if (!empty($item->image)) {

                    if (substr($item->url, 0, 5) == '/user') {
                        $content .= '<div class="content-avatar">
                        <a href="'.$item->url.'" class="avatar"><img src="'.SRC_URL.'/image/'.$item->image.'/32/32/1" /></a>
                        <a href="'.$item->url.'" class="username">'.$item->title.'</a>
                        <span class="datepub">'.$pub_timeago.'</span>
                        </div>';
                    } else {
                        $content .= '<div class="content-image">
                        <a href="'.$item->url.'" class="image"><img src="'.SRC_URL.'/image/'.$item->image.'/90/60/1" /></a>
                        <a href="'.$item->url.'" class="project light-blue">'.$item->title.'</a>
                        <span class="datepub">'.$pub_timeago.'</span>
                        </div>';
                    }
                } else {
                    // solo titulo con enlace
                    $content .= '<div class="content-title">
                        <h5 class="light-blue"><a href="'.$item->url.'" class="project light-blue">'.$item->title.'</a></h5>
                        <span class="datepub">'.$pub_timeago.'</span>
                   </div>';
                }
           } else {
               // solo el timeago
               $content .= '<span class="datepub">'.$pub_timeago.'</span>';
           }

           // y lo que venga en el html
           $content .= '<div class="content-pub">'.$item->html.'</div>';

           $content .= '</div>';

           return $content;
        }

    }
}