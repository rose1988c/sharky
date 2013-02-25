<?php
class Pager implements Iterator {
    // Stuff you set...
    public $page;        // Current page (will be recalculated if outside valid range)
    public $per_page;     // Number of records per page
    public $num_records;  // Total number of records

    // Stuff we calculate...
    public $num_pages;    // Number of pages required to display $num_records records
    public $first_record; // Index of first record on current page
    public $last_record;  // Index of last record on current page

    private $records;    // Used when iterating over object

    // Initialize the pager object with your settings and calculate the resultant values
    public function __construct($page, $per_page, $num_records)
    {
        $this->page = intval($page);
        $this->per_page = intval($per_page);
        $this->num_records = intval($num_records);
        $this->calculate();
    }

    // Do the math.
    // Note: Pager always calculates there to be *at least* 1 page. Even if there are 0 records, we still,
    // by convention, assume it takes 1 page to display those 0 records. While mathematically stupid, it
    // makes sense from a UI perspective.
    public function calculate()
    {
        $this->num_pages = ceil($this->num_records / $this->per_page);
        if($this->num_pages == 0) $this->num_pages = 1;

        if($this->page < 1) $this->page = 1;
        if($this->page > $this->num_pages) $this->page = $this->num_pages;

        $this->first_record = (int) ($this->page - 1) * $this->per_page;
        $this->last_record  = (int) $this->first_record + $this->per_page - 1;
        if($this->last_record >= $this->num_records) $this->last_record = $this->num_records - 1;

        $this->records = range($this->first_record, $this->last_record, 1);
    }

    // Will return current page if no previous page exists
    public function prev_page()
    {
        return max(1, $this->page - 1);
    }

    // Will return current page if no next page exists
    public function next_page()
    {
        return min($this->num_pages, $this->page + 1);
    }

    // Is there a valid previous page?
    public function has_prev_page()
    {
        return $this->page > 1;
    }

    // Is there a valid next page?
    public function has_next_page()
    {
        return $this->page < $this->num_pages;
    }

    public function rewind()
    {
        reset($this->records);
    }

    public function current()
    {
        return current($this->records);
    }

    public function key()
    {
        return key($this->records);
    }

    public function next()
    {
        return next($this->records);
    }

    public function valid()
    {
        return $this->current() !== false;
    }

    public function output($url, $explicit = false)
    {
    	if ($this->num_pages <= 1 && !$explicit) return '';
        $nl = chr(13) . chr(10);
        $html = '<div class="pages">' . $nl;

        if ($this->page > 1) {
        	if($this->page == 2 && stripos($url, 'page%{page}/') != false)
           		$html .= '<a class="nextprev" href="' . str_replace('page%{page}/', '', $url) . '">上一页</a>' . $nl;
           	else 
           		$html .= '<a class="nextprev" href="' . str_replace('%{page}', $this->page - 1, $url) . '">上一页</a>' . $nl;
            	
        } else {
            $html .= '<span class="nextprev">上一页</span>' . $nl;
        }
        
        if ($this->page > 1)
        {
            if ($this->page > 5)
            {
                foreach(range(1, 1) as $i) {
                	if(stripos($url, 'page%{page}/') != false)
                    	$html .= '<a href="' . str_replace('page%{page}/', '', $url) . '">' . $i . '</a>' . $nl;
                    else 
                    	$html .= '<a href="' . str_replace('%{page}', $i, $url) . '">' . $i . '</a>' . $nl;	
                }
                $html .= '<span class="break">...</span>' . $nl;

                if ($this->page > $this->num_pages - 2)
                    $offset = $this->num_pages - 4;
                else
                    $offset = $this->page - 2;

                foreach(range($offset, $this->page - 1) as $i)
                    $html .= '<a href="' . str_replace('%{page}', $i, $url) . '">' . $i . '</a>' . $nl;
            }
            else
            {
            	foreach(range(1, $this->page - 1) as $i) {
                    if($i == 1 && stripos($url, 'page%{page}/') != false) 
                    	$html .= '<a href="' . str_replace('page%{page}/', '', $url) . '">' . $i . '</a>' . $nl;
                    else 
                    	$html .= '<a href="' . str_replace('%{page}', $i, $url) . '">' . $i . '</a>' . $nl;
                }
            }
        }

        $html .= '<span class="current">' . $this->page . '</span>' . $nl;

        if ($this->num_pages - $this->page > 0)
        {
            if ($this->num_pages - $this->page > 5)
            {
                if ($this->page > 3)
                    $offset = $this->page + 2;
                else
                    $offset = 5;

                foreach(range($this->page + 1, $offset) as $i)
                    $html .= '<a href="' . str_replace('%{page}', $i, $url) . '">' . $i . '</a>' . $nl;

                $html .= '<span class="break">...</span>' . $nl;
                foreach(range($this->num_pages, $this->num_pages) as $i)
                    $html .= '<a href="' . str_replace('%{page}', $i, $url) . '"' . (($i == $this->num_pages) ? ' class="end"':'') . '>' . $i . '</a>' . $nl;
            }
            else
            {
                foreach(range($this->page + 1, $this->num_pages) as $i)
                    $html .= '<a href="' . str_replace('%{page}', $i, $url) . '"' . (($i == $this->num_pages) ? ' class="end"':'') . '>' . $i . '</a>' . $nl;
            }
        }

        if ($this->page < $this->num_pages)
            $html .= '<a class="nextprev" href="' . str_replace('%{page}', $this->page + 1, $url) . '">下一页</a>' . $nl;
        else
            $html .= '<span class="nextprev">下一页</span>' . $nl;

        $html .= '</div>' . $nl;
        return $html;
    }
}

class Loop
{
    private $index;
    private $elements;
    private $numElements;

    public function __construct()
    {
        $this->index       = 0;
        $this->elements    = func_get_args();
        $this->numElements = func_num_args();
    }

    public function __tostring()
    {
        return (string) $this->get();
    }

    public function get()
    {
        if($this->numElements == 0) return null;

        $val = $this->elements[$this->index];

        if(++$this->index >= $this->numElements)
            $this->index = 0;

        return $val;
    }

    public function rand()
    {
        return $this->elements[array_rand($this->elements)];
    }
}
