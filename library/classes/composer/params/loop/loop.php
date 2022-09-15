<?php

function vc_loop_form_field( $settings, $value ) {
        $vc_manager = vc_manager();
	$query_builder = new VcLoopSettings( $value );
	$params = $query_builder->getContent();
	$loop_info = '';
	foreach ( $params as $key => $param ) {
		$param_value = vc_loop_get_value( $param );
		if ( ! empty( $param_value ) )
			$loop_info .= ' <b>' . $query_builder->getLabel( $key ) . '</b>: ' . $param_value . ';';
	}

	return '<div class="vc_loop">'
	  . '<input name="' . $settings['param_name'] . '" class="wpb_vc_param_value  ' . $settings['param_name'] . ' ' . $settings['type'] . '_field" type="hidden" value="' . $value . '"/>'
	  . '<a href="#" class="button vc_loop-build ' . $settings['param_name'] . '_button" data-settings="' . rawurlencode( json_encode( $settings['settings'] ) ) . '">' . $vc_manager->l('Build query') . '</a>'
	  . '<div class="vc_loop-info">' . $loop_info . '</div>'
	  . '</div>';
}

function vc_loop_get_value( $param ) {
	$value = array();
	$selected_values = (array)$param['value'];
	if ( isset( $param['options'] ) && is_array( $param['options'] ) ) {
		foreach ( $param['options'] as $option ) {
			if ( is_array( $option ) && isset( $option['value'] ) ) {
				if ( in_array( ( ( $option['action'] === '-' ? '-' : '' ) . $option['value'] ), $selected_values ) ) $value[] = $option['action'] . $option['name'];
			} elseif ( is_array( $option ) && isset( $option[0] ) ) {
				if ( in_array( $option[0], $selected_values ) ) $value[] = $option[1];
			} elseif ( in_array( $option, $selected_values ) ) {
				$value[] = $option;
			}
		}
	} else {
		$value[] = $param['value'];
	}
	return implode( ', ', $value );
}

/**
 * Parses loop settings and creates WP_Query according to manual
 *
 * @link http://codex.wordpress.org/Class_Reference/WP_Query
 */
class VcLoopQueryBuilder {
	protected $args = array();

	public function __construct( $data ) {
		foreach ( $data as $key => $value ) {
			$method = 'parse_' . $key;
			if ( method_exists( $this, $method ) ) {
				call_user_func(array($this, $method), $value);
			}
		}
	}

	// Pages count
	protected function parse_size( $value ) {
		$this->args['posts_per_page'] = $value === 'All' ? -1 : (int)$value;
	}

	// Sorting field
	protected function parse_order_by( $value ) {
		$this->args['orderby'] = $value;
	}

	// Sorting order
	protected function parse_order( $value ) {
		$this->args['order'] = $value;
	}

	// By post types
	protected function parse_post_type( $value ) {
		$this->args['post_type'] = $this->stringToArray( $value );
	}

	// By author
	protected function parse_authors( $value ) {
		$this->args['author'] = $value;
	}

	// By categories
	protected function parse_categories( $value ) {
		$this->args['cats'] = $value;
                
//                $in = array();
//		$cat_ids = $this->stringToArray( $value );
//		foreach ( $cat_ids as $cat ) {
//			$cat = (int)$cat;
//			if ( $cat < 0 ) {
//				$not_in[] = abs( $cat );
//			} else {
//				$in[] = $cat;
//			}
//		}
//		$this->args['cat__in'] = $in; 
	}

	// By taxonomies
	protected function parse_tax_query( $value ) {
		$terms = $this->stringToArray( $value );
		if ( empty( $this->args['tax_query'] ) ) {
			$this->args['tax_query'] = array( 'relation' => 'OR' );
		}
		$negative_term_list = array();
		foreach ( $terms as $term ) {
			if ( (int)$term < 0 ) $negative_term_list[] = abs( $term );
		}
		$terms = get_terms( VcLoopSettings::getTaxonomies(), array( 'include' => array_map( 'abs', $terms ) ) );
		foreach ( $terms as $t ) {
			$operator = in_array( (int)$t->term_id, $negative_term_list ) ? 'NOT IN' : 'IN';
			$this->args['tax_query'][] = array(
				'field' => 'id',
				'taxonomy' => $t->taxonomy,
				'terms' => $t->term_id,
				'operator' => $operator
			);
		}
	}

	// By tags ids
	protected function parse_tags( $value ) {
            $this->args['tags'] = $value;
            
//		$in = $not_in = array();
//		$tags_ids = $this->stringToArray( $value );
//		foreach ( $tags_ids as $tag ) {
//			$tag = (int)$tag;
//			if ( $tag < 0 ) {
//				$not_in[] = abs( $tag );
//			} else {
//				$in[] = $tag;
//			}
//		}
//		$this->args['tag__in'] = $in;
//		$this->args['tag__not_in'] = $not_in;
	}

	// By posts ids
	protected function parse_by_id( $value ) {
		$in = $not_in = array();
		$ids = $this->stringToArray( $value );
		foreach ( $ids as $id ) {
			$id = (int)$id;
			if ( $id < 0 ) {
				$not_in[] = abs( $id );
			} else {
				$in[] = $id;
			}
		}
		$this->args['post__in'] = $in;
		$this->args['post__not_in'] = $not_in;
	}

	public function excludeId( $id ) {
		if ( ! isset( $this->args['post__not_in'] ) ) $this->args['post__not_in'] = array();
		$this->args['post__not_in'][] = $id;
	}

	/**
	 * Converts string to array. Filters empty arrays values
	 *
	 * @param $value
	 * @return array
	 */
	protected function stringToArray( $value ) {
		$valid_values = array();
		$list = preg_split( '/\,[\s]*/', $value );
		foreach ( $list as $v ) {
			if ( strlen( $v ) > 0 ) $valid_values[] = $v;
		}
		return $valid_values;
	}

	public function build() {
		$db = Db::getInstance();
		$context = Context::getContext();
		$id_lang = $context->language->id;
		$id_company = $context->company->id;

		$sql = "SELECT sbpl.* FROM "._DB_PREFIX_."smart_blog_post sbp INNER JOIN "._DB_PREFIX_."smart_blog_post_lang sbpl ON sbp.id_smart_blog_post=sbpl.id_smart_blog_post";
		$sql .= " LEFT JOIN "._DB_PREFIX_."smart_blog_post_shop sbps ON sbpl.id_smart_blog_post=sbps.id_smart_blog_post";
		$sql .= " LEFT JOIN "._DB_PREFIX_."smart_blog_post_category sbpc ON sbpc.id_smart_blog_post=sbp.id_smart_blog_post";
		$sql .= " LEFT JOIN "._DB_PREFIX_."smart_blog_post_tag sbpt ON sbpt.id_post=sbp.id_smart_blog_post";
		$sql .= " WHERE sbps.id_shop={$id_company} AND sbpl.id_lang={$id_lang} AND sbp.active=1";
		
		if(isset($this->args['cats']) && !empty($this->args['cats']) ){
			$sql .= " AND sbpc.id_smart_blog_category IN ({$this->args['cats']})";
		}
		if(isset($this->args['tags']) && !empty($this->args['tags']) ){
			$sql .= " AND sbpt.id_tag IN ({$this->args['tags']})";
		}
		if(isset($this->args['orderby']) && !empty($this->args['orderby']) ){                    
			if($this->args['orderby'] == 'meta_title' || $this->args['orderby'] == 'link_rewrite'){
				$orderby = "sbpl.{$this->args['orderby']}";
			}elseif($this->args['orderby'] == 'date'){
				$this->args['orderby'] = 'created';
				$orderby = "sbp.{$this->args['orderby']}";
			}else{
				$orderby = "sbp.{$this->args['orderby']}";
			}                    
			$sql .= " ORDER BY {$orderby}";
			if(isset($this->args['order']) && !empty($this->args['order']) ){
				$sql .= " {$this->args['order']}";
			}
		}                
		if(isset($this->args['posts_per_page']) && !empty($this->args['posts_per_page']) ){
			$sql .= " LIMIT {$this->args['posts_per_page']}";
		}
		$results = $db->executeS($sql,true,false);
		foreach ($results as $result_key => $result_val) {
			$results[$result_key]['content'] = $result_val['short_description'];
		}
		return array($this->args, $results);
//		return array( $this->args, new WP_Query( $this->args ) );
	}
}

class VcLoopSettings {
	// Available parts of loop for WP_Query object.
	protected $content = array();
	protected $parts;
	protected $query_parts = array(
		'size', 
		'order_by', 
		'order',
		'categories', 
		'tags',
	);

	function __construct( $value, $settings = array() ) {
        $vc_manager = vc_manager();
		$this->parts = array(
			'size' => $vc_manager->l('Post Count'),
			'order_by' => $vc_manager->l('Order By'),
			'order' => $vc_manager->l('Order'),
			'categories' => $vc_manager->l('Categories'),
			'tags' => $vc_manager->l('Tags'),
		);
		$this->settings = $settings;
                
                
		// Parse loop string
		$data = $this->parseData( $value );
                
		foreach ( $this->query_parts as $part ) {
			$value = isset( $data[$part] ) ? $data[$part] : '';
			$locked = $this->getSettings( $part, 'locked' ) === 'true';
			// Predefined value check.
			if ( ! is_null( $this->getSettings( $part, 'value' ) ) && $this->replaceLockedValue( $part )
			  && ( $locked === true || strlen( (string)$value ) == 0 )
			) {
				$value = $this->settings[$part]['value'];
			} elseif ( ! is_null( $this->getSettings( $part, 'value' ) ) && ! $this->replaceLockedValue( $part )
			  && ( $locked === true || strlen( (string)$value ) == 0 )
			) {
				$value = implode( ',', array_unique( explode( ',', $value . ',' . $this->settings[$part]['value'] ) ) );
			}
			// Find custom method for parsing
			if ( method_exists( $this, 'parse_' . $part ) ) {
				$method = 'parse_' . $part;
				//$this->content[$part] = $this->$method( $value );
				$this->content[$part] = call_user_func(array($this, $method), $value);
			} else {
				$this->content[$part] = $this->parseString( $value );
			}
                        
			// Set locked if value is locked by settings
			if ( $locked ) $this->content[$part]['locked'] = true;
			//
			if ( $this->getSettings( $part, 'hidden' ) === 'true' ) {
				$this->content[$part]['hidden'] = true;
			}
                        
		}
	}

	protected function replaceLockedValue( $part ) {
		return in_array( $part, array( 'size', 'order_by', 'order' ) );
	}

	public function getLabel( $key ) {
		return isset( $this->parts[$key] ) ? $this->parts[$key] : $key;
	}

	public function getSettings( $part, $name ) {            
		$settings_exists = isset( $this->settings[$part] ) && is_array( $this->settings[$part] );
		return $settings_exists && isset( $this->settings[$part][$name] ) ? $this->settings[$part][$name] : null;
	}

	public function parseString( $value ) {
		return array( 'value' => $value );
	}

	protected function parseDropDown( $value, $options = array() ) {
		return array( 'value' => $value, 'options' => $options );
	}

	protected function parseMultiSelect( $value, $options = array() ) {
		return array( 'value' => explode( ',', $value ), 'options' => $options );
	}

	public function parse_order_by( $value ) {
                $vc_manager = vc_manager();
		return $this->parseDropDown( $value, array(
			//array('none', $vc_manager->l("None")),
			array( 'created', $vc_manager->l("Date") ),
			array( 'id_smart_blog_post', $vc_manager->l("ID") ),
//			array( 'author', $vc_manager->l("Author") ),
			array( 'meta_title', $vc_manager->l("Title") ),
			//'name',
			array( 'modified', $vc_manager->l("Modified") ),
			//'parent',
			array( 'position', $vc_manager->l("Position") ),
//			array( 'comment_count', $vc_manager->l("Comment count") ),
			array( 'link_rewrite', $vc_manager->l("Slug") ),
			
		) );
	}

	public function parse_order( $value ) {
                $vc_manager = vc_manager();
		return $this->parseDropDown( $value, array(
			array( 'ASC', $vc_manager->l("Ascending") ),
			array( 'DESC', $vc_manager->l("Descending") )
		) );
	}

	public function parse_post_type( $value ) {
		$options = array();
		$args = array(
			'public' => true
		);
		$post_types = get_post_types( $args );
		foreach ( $post_types as $post_type ) {
			if ( $post_type != 'attachment' ) {
				$options[] = $post_type;
			}
		}
		return $this->parseMultiSelect( $value, $options );
	}

	public function parse_authors( $value ) {
		$options = $not_in = array();
		if ( empty( $value ) ) return $this->parseMultiSelect( $value, $options );
		$list = explode( ',', $value );
		foreach ( $list as $id ) {
			if ( (int)$id < 0 ) $not_in[] = abs( $id );
		}
		$users = get_users( array( 'include' => array_map( 'abs', $list ) ) );
		foreach ( $users as $user ) {
			$options[] = array(
				'value' => (string)$user->ID,
				'name' => $user->data->user_nicename,
				'action' => in_array( (int)$user->ID, $not_in ) ? '-' : '+'
			);
		}
		return $this->parseMultiSelect( $value, $options );
	}

	public function parse_categories( $value ) {
		$options = $not_in = array();
		if ( empty( $value ) ) return $this->parseMultiSelect( $value, $options );
                
		$list = explode( ',', $value );
		foreach ( $list as $id ) {
			if ( (int)$id < 0 ) $not_in[] = abs( $id );
		}
                $id_lang = (int)Context::getContext()->language->id;
        
        $titlefield = 'meta_title';
        $smartblog = Module::getInstanceByName('smartblog');
        if(Tools::version_compare($smartblog->version, '2.1', '>=')){
            $titlefield = 'name';
        }
                
		$list = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'smart_blog_category` sbc INNER JOIN `'._DB_PREFIX_.'smart_blog_category_lang` sbcl ON(sbc.`id_smart_blog_category` = sbcl.`id_smart_blog_category` AND sbcl.`id_lang` = '.(int)($id_lang).')
		INNER JOIN `'._DB_PREFIX_.'smart_blog_category_shop` sbs ON sbs.id_smart_blog_category = sbc.id_smart_blog_category WHERE sbc.id_smart_blog_category IN('.$value.') AND sbc.`active`= 1 AND sbs.id_shop = '.(int) Context::getContext()->shop->id);

//                $list = get_categories( array( 'include' => array_map( 'abs', $list ) ) );
                
		foreach ( $list as $obj ) {
			$options[] = array(
				'value' => (string)$obj['id_smart_blog_category'],
				'name' => $obj[$titlefield],
				'action' => in_array( (int)$obj['id_smart_blog_category'], $not_in ) ? '-' : '+'
			);
		}
		return $this->parseMultiSelect( $value, $options );
                
	}

	public function parse_tags( $value ) {
		
		$options = $not_in = array();
		if ( empty( $value ) ) return $this->parseMultiSelect( $value, $options );
                
		$list = explode( ',', $value );
		foreach ( $list as $id ) {
			if ( (int)$id < 0 ) $not_in[] = abs( $id );
		}
                $id_lang = (int)Context::getContext()->language->id;
        
        
        $smartblog = Module::getInstanceByName('smartblog');
        if(Tools::version_compare($smartblog->version, '2.1', '>=')){
            $sql = 'SELECT * FROM `'._DB_PREFIX_.'smart_blog_tag` WHERE id_lang='.$id_lang.' AND id_tag IN('.$value.')';
			$list = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
			foreach ( $list as $obj ) {
				$options[] = array(
					'value' => (string)$obj['id_tag'],
					'name' => $obj['name'],
					'action' => in_array( (int)$obj['id_tag'], $not_in ) ? '-' : '+'
				);
			}
			return $this->parseMultiSelect( $value, $options );
        }
	}

	public function parse_tax_query( $value ) {
		$options = $not_in = array();
		if ( empty( $value ) ) return $this->parseMultiSelect( $value, $options );
		$list = explode( ',', $value );
		foreach ( $list as $id ) {
			if ( (int)$id < 0 ) $not_in[] = abs( $id );
		}
		$list = get_terms( VcLoopSettings::getTaxonomies(), array( 'include' => array_map( 'abs', $list ) ) );
		foreach ( $list as $obj ) {
			$options[] = array(
				'value' => (string)$obj->term_id,
				'name' => $obj->name,
				'action' => in_array( (int)$obj->term_id, $not_in ) ? '-' : '+'
			);
		}
		return $this->parseMultiSelect( $value, $options );
	}

	public function parse_by_id( $value ) {
		$options = $not_in = array();
		if ( empty( $value ) ) return $this->parseMultiSelect( $value, $options );
		$list = explode( ',', $value );
		foreach ( $list as $id ) {
			if ( (int)$id < 0 ) $not_in[] = abs( $id );
		}
		$list = get_posts( array( 'post_type' => 'any', 'include' => array_map( 'abs', $list ) ) );

		foreach ( $list as $obj ) {
			$options[] = array(
				'value' => (string)$obj->ID,
				'name' => $obj->post_title,
				'action' => in_array( (int)$obj->ID, $not_in ) ? '-' : '+'
			);
		}
		return $this->parseMultiSelect( $value, $options );
	}

	public function render() {
		echo json_encode( $this->content );
	}

	public function getContent() {
		return $this->content;
	}

	/**
	 * get list of taxonomies which has no tags and categories items.
	 *
	 * @static
	 * @return array
	 */
	public static function getTaxonomies() {
		$taxonomy_exclude = (array)apply_filters( 'get_categories_taxonomy', 'category' );
		$taxonomy_exclude[] = 'post_tag';
		$taxonomies = array();
		foreach ( get_taxonomies() as $taxonomy ) {
			if ( ! in_array( $taxonomy, $taxonomy_exclude ) ) $taxonomies[] = $taxonomy;
		}
		return $taxonomies;
	}

	public static function buildDefault( $settings ) {
		if ( ! isset( $settings['settings'] ) || ! is_array( $settings['settings'] ) ) return '';
		$value = '';
		foreach ( $settings['settings'] as $key => $val ) {
			if ( isset( $val['value'] ) ) $value .= ( empty( $value ) ? '' : '|' ) . $key . ':' . $val['value'];
		}
		return $value;
	}

	public static function buildWpQuery( $query, $exclude_id = false ) {
           
		$data = self::parseData( $query );
               //  var_dump($data);die();
		$query_builder = new VcLoopQueryBuilder( $data );
		if ( $exclude_id ) $query_builder->excludeId( $exclude_id );
		return $query_builder->build( $exclude_id );
	}

	public static function parseData( $value ) {
		$data = array();
		$values_pairs = preg_split( '/\|/', $value );
		foreach ( $values_pairs as $pair ) {
			if ( ! empty( $pair ) ) {
				list( $key, $value ) = preg_split( '/\:/', $pair );
				$data[$key] = $value;
			}
		}
		return $data;
	}
}

/**
 * Suggestion list for wp_query field
 *
 */
class VcLoopSuggestions {
//	protected $content = array();
	protected $content = '';
	protected $exclude = array();
	protected $field;

	function __construct( $field, $query, $exclude ) {
		$this->exclude = explode( ',', $exclude );
		$method_name = 'get_' . preg_replace( '/_out$/', '', $field );
		if ( method_exists( $this, $method_name ) ) {
			call_user_func(array($this, $method_name), $query);
		}
	}

	public function get_authors( $query ) {
		$args = ! empty( $query ) ? array( 'search' => '*' . $query . '*', 'search_columns' => array( 'user_nicename' ) ) : array();
		if ( ! empty( $this->exclude ) ) $args['exclude'] = $this->exclude;
		$users = get_users( $args );
		foreach ( $users as $user ) {
			$this->content[] = array( 'value' => (string)$user->ID, 'name' => (string)$user->data->user_nicename );
		}
	}

	public function get_categories( $query ) {
//		$args = ! empty( $query ) ? array( 'search' => $query ) : array();
            
        $exclude = $exid = '';
        if ( ! empty( $this->exclude ) ) {

            foreach($this->exclude as $k => $v){
                if(empty($v))
                    continue;
                if($k > 0 && !empty($exid))
                    $exid .= ',';
                $exid .= $v;
            }
            if(!empty($exid)){
                $exclude = 'sbc.id_smart_blog_category NOT IN(';
                $exclude .= $exid;
                $exclude .= ') AND ';
            }
        }
        $limit = vc_post_param('limit') ? vc_post_param('limit') : 20;
//		$categories = get_categories( $args );
//		$categories = BlogCategory::getCategory( $args );
        $id_lang = (int)Context::getContext()->language->id;

        
        
//        echo 'SELECT * FROM `'._DB_PREFIX_.'smart_blog_category` sbc INNER JOIN `'._DB_PREFIX_.'smart_blog_category_lang` sbcl ON(sbc.`id_smart_blog_category` = sbcl.`id_smart_blog_category` AND sbcl.`id_lang` = '.(int)($id_lang).')
//    INNER JOIN `'._DB_PREFIX_.'smart_blog_category_shop` sbs ON sbs.id_smart_blog_category = sbc.id_smart_blog_category WHERE '.$exclude.'sbc.`active`= 1 AND sbs.id_shop = '.(int) Context::getContext()->shop->id.' AND sbcl.name LIKE "%'.$query.'%" LIMIT '.$limit;
        
        $titlefield = 'meta_title';
        $smartblog = Module::getInstanceByName('smartblog');
        if(Tools::version_compare($smartblog->version, '2.1', '>=')){
            $titlefield = 'name';
        }

        $categories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS('
        SELECT * FROM `'._DB_PREFIX_.'smart_blog_category` sbc INNER JOIN `'._DB_PREFIX_.'smart_blog_category_lang` sbcl ON(sbc.`id_smart_blog_category` = sbcl.`id_smart_blog_category` AND sbcl.`id_lang` = '.(int)($id_lang).')
INNER JOIN `'._DB_PREFIX_.'smart_blog_category_shop` sbs ON sbs.id_smart_blog_category = sbc.id_smart_blog_category WHERE '.$exclude.'sbc.`active`= 1 AND sbs.id_shop = '.(int) Context::getContext()->shop->id.' AND sbcl.'.$titlefield.' LIKE "%'.$query.'%" LIMIT '.$limit);

        if(empty($categories)){ $this->content = ''; return;}

		foreach ( $categories as $cat ) {
			$this->content .= $cat[$titlefield] . '|';
			$this->content .= $cat['id_smart_blog_category'] . "\n";
		}
	}

	public function get_tags( $query ) {
		$smartblog = Module::getInstanceByName('smartblog');
        if(Tools::version_compare($smartblog->version, '2.1', '>=')){
			$id_lang = (int)Context::getContext()->language->id;
			$limit = vc_post_param('limit') ? vc_post_param('limit') : 20;
			$exclude = $exid = '';
			if ( ! empty( $this->exclude ) ) {

				foreach($this->exclude as $k => $v){
					if(empty($v))
						continue;
					if($k > 0 && !empty($exid))
						$exid .= ',';
					$exid .= $v;
				}
				if(!empty($exid)){
					$exclude = 'id_tag NOT IN(';
					$exclude .= $exid;
					$exclude .= ') AND ';
				}
			}
			$sql = 'SELECT * FROM `'._DB_PREFIX_.'smart_blog_tag` WHERE '.$exclude.' id_lang='.$id_lang.' AND name LIKE "%'.$query.'%" LIMIT '.$limit;
			$categories = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql);
			
			if(empty($categories)){ $this->content = ''; return;}

			foreach ( $categories as $cat ) {
				$this->content .= $cat['name'] . '|';
				$this->content .= $cat['id_tag'] . "\n";
			}
		}
	}

	public function get_tax_query( $query ) {
		$args = ! empty( $query ) ? array( 'search' => $query ) : array();
		if ( ! empty( $this->exclude ) ) $args['exclude'] = $this->exclude;
		$tags = get_terms( VcLoopSettings::getTaxonomies(), $args );
		foreach ( $tags as $tag ) {
			$this->content[] = array( 'value' => $tag->term_id, 'name' => $tag->name );
		}
	}

	public function get_by_id( $query ) {
		$args = ! empty( $query ) ? array( 's' => $query, 'post_type' => 'any' ) : array( 'post_type' => 'any' );
		if ( ! empty( $this->exclude ) ) $args['exclude'] = $this->exclude;
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$this->content[] = array( 'value' => $post->ID, 'name' => $post->post_title );
		}
	}

	public function render() {
//		echo json_encode( $this->content );
		echo $this->content;
	}
}

/**
 * Build WP_Query object from query string.
 * String created by loop controllers
 *
 * @param $query
 * @return string
 */
function vc_build_loop_query( $query, $exclude_id = false ) {
	return VcLoopSettings::buildWpQuery( $query, $exclude_id );
}

function vc_get_loop_suggestion() {
//	$loop_suggestions = new VcLoopSuggestions(vc_post_param('field'), vc_post_param('query'), vc_post_param('exclude'));
	$loop_suggestions = new VcLoopSuggestions(Tools::getValue('field'), Tools::getValue('q'), Tools::getValue('excludeIds'));
	$loop_suggestions->render();
	die();
}
function vc_get_loop_settings_json() {
	$loop_settings = new VcLoopSettings(vc_post_param('value'), vc_post_param('settings'));
	$loop_settings->render();
	die();
}
//add_action( 'wp_ajax_wpb_get_loop_suggestion', 'vc_get_loop_suggestion' ); // need to add ajax action
//add_action('wp_ajax_wpb_get_loop_settings', 'vc_get_loop_settings_json');

JsComposer::$sds_action_hooks['wpb_get_loop_suggestion'] = 'vc_get_loop_suggestion';
JsComposer::$sds_action_hooks['wpb_get_loop_settings'] = 'vc_get_loop_settings_json';

function vc_loop_include_templates() {
        
	require_once vc_path_dir( 'TEMPLATES_DIR', 'params/loop/templates.html' );
}

JsComposer::$sds_action_hooks['ps_admin_footer'] = 'vc_loop_include_templates';


//add_action('admin_footer', 'vc_loop_include_templates');

function vc_set_loop_default_value($param) {
        
	if ( empty( $param['value']) && isset($param['settings'])) {
                
		$param['value'] = VcLoopSettings::buildDefault( $param );
	}
	return $param;
}
JsComposer::$sds_action_hooks['vc_mapper_attribute_loop'] = 'vc_set_loop_default_value';

//add_filter('vc_mapper_attribute_loop', 'vc_set_loop_default_value'); //find if any apply_filters found regarding with it. Then decide.