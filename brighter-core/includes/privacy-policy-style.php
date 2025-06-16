<?php 

add_action('wp_head', function() {
    if (is_page('privacy-policy')) { // Can also use page ID
        ?>
        <style>
        /* Privacy Policy & Terms CSS */



.terms-tocs > li a{
        text-decoration:none;
        font-family: "Noto Sans",sans-serif;
        font-weight: 500;
}
p,
.terms-text,
ul,
li{
  list-style-type: none; 
  line-height: 1.5;
  font-family: "Noto Sans",sans-serif!important;
  
  color: #2b2b2b;
}

li{
  padding-bottom:10px;
 }
 
h2,h3,h4{
      font-size:1.1em!important;
      margin: 10px 0 10px 0;
    line-height: 1;
     font-weight: 500;
         font-family: "Noto Sans",sans-serif!important;

} 
 

.c01{
  padding-left: 20px;
}
.c02{
  padding-left: 40px;
}
.c03{
  padding-left: 60px;
}
.c04{
  padding-left: 90px;
}
.c05{
  padding-left: 100px;
}
         </style>
        <?php
    }
});
