# SimPHPfy
SimPHPfy is my individual project when studying CSCI4140 Open-Source Software Development. It is no longer maintained and serves only as an archive for future project. It supports routing with custom rules, HTML template system, access MySQL database using Object-orieted like syntax, and also support quickly creating a few demo-purpose systems using scaffold including a featureless simple Member system and a user tracking image.
  
## Demo Instructions
* php simphpfy scaffold:generate Member
* datasource.php configuration
* php simphpfy db:migrate
* app/routes.php, copy content
* http://127.0.0.1/simphpfy
* http://127.0.0.1/simphpfy/view/12, /16
* http://127.0.0.1/simphpfy/redirect/123.jpg
* http://127.0.0.1/simphpfy/redirect/member
* Router/routes,php
* http://127.0.0.1/simphpfy/Member/
* php simphpfy db:create MemberField
* php simphpfy mvc:generate_model MemberField
* create_memberfield.schema, copy content
* php simphpfy db:migrate
* Model/MemberFieldModel.php, copy content
* illustrate Model definition
* Controller/Member.php, copy content
* View/Member/Edit.html, copy content
* Illustrate form helper and input helper
* Member login
* Model/MemberModel.php, copy content
* Member registration
* Member edit
* Test case: non-integer age, introduction length less than 4
* Try to show
* View/Member/Show.html, copy content
* Create header.html
* <% render file="header.html" controller="Member" dynamic="TRUE" behaviour="direct" %>
* <% render file="Index.js" controller="Member” static="TRUE" behaviour="url" %>
* php simphpfy mvc:generate Post
* php simphpfy db:create Post
* PostModel.php, copy content
* Post.php, copy content
* create_post.schema, copy content
* php simphpfy db:migrate
* Query illustration
* php simphpfy mvc:generate Track
* php simphpfy db:create Track
* php simphpfy db:imgrate
* copy image
* mv /var/www/html/cuhk.png app/public/
* add to header
