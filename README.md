# moodle-python
Self marking python activity module for moodle

# Description
This module for moodle allows teachers to create activities that allow students to try, debug and extend python code in their browser.

Teachers can define tests that students' code will be checked against and students will receive instant feedback on what they've accomplished.

Students' scores are saved into the gradebook and teachers can get a live update of the progress of students in each group.

# Disclaimer
This module is under development and comes with no guarantees.

# About
Python code is simulated in the browser on the client side so there's no opportunity to run malicious code on the server. The python simulator is based on [create.withcode.uk](https://create.withcode.uk) which in turn is based on [Skulpt](http://www.skulpt.org/)

This project is designed and maintained by P. Dring at Fulford School, York.

The python activities are each split into three sections:

- Try it: give students some code to try out with an explanation of what it does
- Debug it: give students some broken code to attempt to fix
- Extend it: give students some open ended challenges to build on what they've learnt

# Installation instructions.
Download the project files and unzip. Inside the mod folder is a folder called withcode. This needs to go in the mod folder on your moodle installation page. Login as an administrator and moodle will detect the new plugin and install it. You can then create new python modules.
