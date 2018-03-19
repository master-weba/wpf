<?php
namespace wpf\wp;
/**
 * Class Pagination
 * @package app\widgets
 */
final class Pagination
{
    /**
     * Query param name
     */
    private $_query_key = 'pg';
    
    /**
     * Count of paginate links. It has a setter.
     *
     * @var int
     */
    private $_count = 5;
    
    /**
     * Max quantity of pages.
     *
     * @var float|int
     */
    private $_max = 1;
    
    /**
     * It's True when the subject is WP_Query object;
     *
     * @var bool
     */
    private $_is_wp = false;
    
    /**
     * By default $_per_page parameter equally 10.
     * It can be modified with constructor.
     * Does not affect when the subject is WP_Query object
     *
     * @var int
     */
    private $_per_page;

    /**
     * Current page nubmer
     *
     * @var
     */
    private $_current;

    /**
     * Pagination constructor.
     * Create this class before get_title() calling for correct counter working.
     *
     * @param mixed $subject It can be WP_Query object, array, or int
     * @param int $per_page | Not working with WP_Query subject
     */
    public function __construct( $subject, int $per_page = 10 ) {
        $this->_per_page = $per_page;
        if ( is_array( $subject ) ) {
            $subject = count( $subject );
        }
        if ( is_a( $subject, '\WP_Query' ) ) {
            $this->_is_wp = true;
            // Takes values from WordPress
            $this->_max = $subject->max_num_pages;
            $this->_per_page = $subject->post_count;
        } elseif ( is_int( $subject ) || is_string( $subject ) ) {
            $this->_max = ceil( $subject / $this->_per_page );
        }
        $this->_current = $this->current();
        // Save to the WPF Store
        if ( function_exists('app') ) {
            app()->pagination_state = [ $this->current(), $this->_max ];
        }
    }
    
    /**
     * This function creates links and save them to $this->_items array.
     * Each of links is array like [$link, $isActive]
     */
    public function items() {
        if ( $this->_max <= 1 ) {
            return [];
        }
        $end = min( $this->_max, $this->_current + floor( $this->_count / 2 ) );
        $start = max( 1, $end - $this->_count + 1 );
        $items = [];
        if ( $start != 1 ) {
            $items[] = $this->item(__('В начало'), 1);
        }
        for ( $i = $start; $i <= $end; $i ++ ) {
            $items[] = $this->item( $i, $i, ($i == $this->_current) );
        }
        if ( $this->_current != $this->_max ) {
            $items[] = $this->item(__('Следующая >'), ($this->_current + 1) );
        }
        return $items;
    }

    /**
     * Returns prev and next urls.
     * 
     * @return array
     */
    public function pager() {
        $prev = ( $this->_current > 1 ) ? $this->url( $this->_current - 1 ) : false;
        $next = ( $this->_current >= $this->_max ) ? false : $this->url( $this->_current + 1 );
        
        return compact('prev', 'next');
    }

    /**
     * @param $anchor
     * @param $page_number
     * @param bool $is_active
     * @return array
     */
    private function item( $anchor, $page_number, bool $is_active = false ): array {
        $values = [ $anchor, $this->url( $page_number ), $is_active ];

        return array_combine( ['anchor', 'url', 'isActive'], $values );
    }

    /**
     * @param int $page_number
     * @return string
     */
    private function url( int $page_number ): string {
        $url = home_url( $_SERVER['REQUEST_URI'] );
        if ( $page_number == 1 && ! $this->_is_wp ) {
            return remove_query_arg( $this->_query_key, $url );
        }
        return add_query_arg( $this->_is_wp ? 'paged' : $this->_query_key, $page_number, $url );
    }
    
    /**
     * It's know about current page number.
     *
     * @return int
     */
    public function current(): int {
        return max( absint( $_GET[ $this->_query_key ] ?? 1 ), get_query_var( 'paged' ) );
    }
    
    /**
     * @param int $value
     */
    public function setCount( int $value ) {
        $this->_count = $value;
    }
    
    /**
     * @param string $value
     */
    public function setQueryKey( string $value ) {
        $this->_query_key = $value;
    }
}
