# random_assignment
Random assignment is a plugin fom LMS Moodle

Random assignment is an attempt to handle larger classes of students.
For this purpose, we generate a series of html (txt, pdf) files with the
same structure but different numbers. In addition, we generate
corresponding files with hints to solutions for teachers.

Random assignment is a plugin for assignments with following
properties:

- Teacher specifies one or more files for
  assignments and (optionally) solution files with the same names

- Each student gets one of these files randomly 

- In the assignment feedback page, teacher sees the assignment file
  and optionally the solution file as well.

## Installation procedure

1. Copy directory random/ to moodle/mod/assign/submission/

2. If needed, add your language. Language files are located in
   moodle/mod/assign/submission/random/lang

## Creation of an assignment

1. Create activity: Assignment 
1a. in Submission settings enable: Random assignment: yes and specify files for assignments and solutions.
1b. in Submission settings specify files for assignments (Random assignment files) and solutions (Random assignment solutions).

Student sees link to the assignment and, optionally its content if it
is a html or txt file (utf-8 encoded). Teacher sees links to both files when grading 
a student on the feedback page. Teacher also sees links to all files 
on the assignment and feedback pages.


miroslav.fikar[at]gmail.com
lubos.cirka[at]stuba.sk
April 2013

Citation:
@inproceedings{uiam1374,
author	 = 	{{\v{C}}irka, {\v{L}}. and Kal\'uz, M. and Fikar, M.},
title	 = 	{New Features in Random Assignment -- Module for LMS Moodle},
booktitle	 = 	{Zborn\'ik pr\'ispevkov z medzin\'arodnej vedeckej konferencie: Inova\v{c}n\'y proces v e-learningu},
year	 = 	{2013},
pages	 = 	{1-6},
publisher	 = 	{Ekon\'om},
url	 = 	{https://www.uiam.sk/assets/publication_info.php?id_pub=1374}
} 

<a href="https://www.uiam.sk/assets/publication_info.php?id_pub=1374">Full paper</a>
