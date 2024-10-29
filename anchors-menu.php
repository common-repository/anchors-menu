<?php
/*
Plugin Name: Anchors Menu
Description: Check Wordpress static pages content and create a widget menu with links that point to words between the HTML tags you chose.
Author: Gonçalo Rodrigues 
Version: 1.2
Author URI: http://www.goncalorodrigues.com
*/

/* Copyright 2010  Gonçalo Rodrigues  (email : gonafr [AT] gmail [DOT] com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
//ERROR_REPORTING(E_ALL);

$count = -1;

add_action("init", "anc_insert_init");
add_filter("the_content", "anc_add_link_text");


// renders the list in your theme;
function anc_insert() 
{	 
	$tags_to_parse = "h2";
	
	$current_page_id = get_the_ID();
	$page_data = get_page($current_page_id);
	$current_page_name = $page_data->post_name;
	
	$current_page_cat_id = get_cat_ID($current_page_name);

	$page_id = get_the_ID();
	
	// if it's blog style page
	if (is_home()){
		$count = 0;
		$content = "";
		if ( have_posts() ) : while ( have_posts() ) : the_post();
			$id_post = get_the_ID();
			$post = get_post($id_post);
		
			$content .= "<a name=\"$count\"></a>";
			$content .= $post->post_content;

			$count++;
		endwhile; else:
			//_e("Sorry, no posts matched your criteria.");
		endif;
		
		anc_list_menu($content);
		//echo "Sorry but this plugin only work on Wordpress pages.";
	}
	
	//if it's page style page
	else if(is_page()){
		$page_id = get_the_ID();
		$page_data = get_page( $page_id );
		//$title = $page_data->post_title; // Get title
		$content = $page_data->post_content;
		//if content is empty i will fetch all pages that are child of the current page and get their titles
		
		$fetch_children_pages = true;
		if (fetch_children_pages == true && $content==""){
			$content = "";
			$pages = get_pages('child_of='.$page_id.'&sort_column=post_date&sort_order=desc');
			
			foreach($pages as $page)
			{		
				$content .= "<".$tags_to_parse.">".$page->post_title."</".$tags_to_parse.">";
				if(!$content)
					continue;
				$count++;
			}
		}	
		anc_list_menu($content);
	}
	
	// if it's single post
	else if (is_single()){
		
		$content = "";
		$content .= get_the_content();
		anc_list_menu($content);
		//echo "Sorry but this plugin only work on Wordpress pages.";
	}
	
	//if it's a category page
	else if(is_category){
		
		//echo $current_page_cat_id;
		//echo $current_page_name;
		//echo $current_page_id;
		
		$current_cat = get_the_category();
		//echo 'teste'.$current_cat[0]->cat_name;
		
		$current_cat_id = get_cat_ID($current_cat[0]->cat_name);
		$posts = get_posts('$category_name='.$current_cat[0]->cat_name);
		$content = "";

		if ( have_posts() ) : while ( have_posts() ) : the_post();

			$id_post = get_the_ID();
			$post = get_post($id_post);
			$content .= "<".$tags_to_parse.">".$post->post_title."</".$tags_to_parse.">";

		endwhile; else:
			//_e("Sorry, no posts matched your criteria.");

		endif;
		anc_list_menu($content);	
		//echo "Sorry but this plugin only work on Wordpress pages.";
	}
	
	else {
		//_e("Error: This page is not a tradicional Wordpress Page or a Wordpress Blog!");
	}
}

// prints the menu with the links to the titles
function anc_list_menu($content){
	// list all tags
	$foo_tags = anc_get_tags($content); 

	if($foo_tags[1] != 0){
		$foo = -1;
		echo "<ul>";

		foreach($foo_tags[0] as $key => $val){
			$foo++;
			echo "<li><a href=\"#$foo\">".$val."</a></li>";
		}

		echo "</ul>";
	}else{
		//no tags found
		//_e("Not found any tag of the type that was selected to be parsed.");
	}
}

// retrieve all words between tags
function anc_get_tags($content){
	global $tags_to_parse;
	
	$options = get_option("anc_tags");
	$tags_to_parse = $options["anc_tags"];
	
	$pattern_search = "/(<".$tags_to_parse.".*>)(.*)(<\/".$tags_to_parse.">)/isxmU";
	preg_match_all($pattern_search, $content, $patterns);
	$res = array();
	array_push($res, $patterns[2]);
	array_push($res, count($patterns[2]));

	return $res;
}

// insert widget
function anc_insert_init()
{
	//register the widget
	register_sidebar_widget("Anchors Menu", "anc_widget_insert");
	//register the widget control
	register_widget_control("Anchors Menu", "anc_widget_insert_control");   
}

function anc_widget_insert($args) {
	global $title, $tags_to_parse;
	
	extract($args);
	
	//get our options
	$options = get_option("anc_title");
	$title = $options["anc_title"];

	$options = get_option("anc_tags");
	$tags_to_parse = $options["anc_tags"];

	echo $before_widget;
	/*Insert any headers you want in the next line, between "?>" and "<?". Leave blank for no header. */
	echo $before_title . $title . $after_title;
	anc_insert();

	echo $after_widget;
}

// responsable for options in backoffice
function anc_widget_insert_control() {
	global $title, $tags_to_parse;
	
	//get saved options if user change things
	//handle user input
	if (isset($_POST["anc_insert_submit"])){
		$foo_title = strip_tags(stripslashes($_POST["anc_title"]));
		$foo_tags = strtolower(stripslashes($_POST["anc_tags"]));
		
		$options["anc_title"] = $foo_title;
		$options["anc_tags"] = $foo_tags;

		update_option("anc_title", $options);
		update_option("anc_tags", $options);
	}
	else {
		//default options
		$options["anc_title"] = "Menu";
		$options["anc_tags"] = "h2";

		update_option("anc_title", $options);
		update_option("anc_tags", $options);
	}
	
	//get our options
	$options = get_option("anc_title");
	$title = $options["anc_title"];

	$options = get_option("anc_tags");
	$tags_to_parse = $options["anc_tags"];

	//print the widget control
	include("anc-insert-widget-control.php");
}

// adds anchors to content tags
function anc_add_link_text($content){
	global $tags_to_parse, $count;
		
	$options = get_option("anc_tags");
	$tags_to_parse = $options["anc_tags"];
	
	$pattern_search = array();
	$pattern_search = "/(<".$tags_to_parse.".*>)(.*)(<\/".$tags_to_parse.">)/isxmU";

 	return preg_replace_callback($pattern_search, "anc_replacement", $content, -1);
}

// aux funtion to add_link_text
function anc_replacement($matches){
	global $tags_to_parse, $count;
	$count++;
	return "<a name=\"".$count."\"></a>"."<".$tags_to_parse.">".$matches[2]."</".$tags_to_parse.">";
}

?>