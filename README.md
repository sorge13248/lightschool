# [LightSchool](https://lightschool.francescosorge.com/ "LightSchool homepage") — Your learning mate
LightSchool is a powerful, customizable and very easy to use web app that aims to provide a great way for improving your student and teacher life.

## Features ##
* Take notes
* Upload files
* Organize files in folders
* Pin important files on the desktop
* Add new diary events and assign them a priority
* Manage school's timetable by adding subjects to the Timetable app
* Share notes, files, diary events, folders and contacts with users on the platform
* Send messages to your classmates and teachers
* Save users in your contacts to reach them easily
* Preview Office files in your browser without having to download them
* Enhance your account security by activating 2FA and enforcing your privacy rules
* Customize platform's color scheme, apply themes and wallpapers
* Pin your favorite app in your taskbar (and rearrange them) and leave the other in the Application Launcher
* Dark theme is bundled!
* Project files on the whiteboard of your classroom, so your fellow classmates and teacher can see your works
* 100% compatible with mobile and touch devices
* Much more!

## Cloud ##
LightSchool can be used without any server nor programming knowledge. Just head to [lightschool.francescosorge.com](https://lightschool.francescosorge.com/) and signup for a free account.

## Self-hosted ##
By self-hosting LightSchool, you can tweak platform parameters to match your needs (like increasing disk space available). Please note that by doing that, you will be placed on a sort of "private track" and your LightSchool will not comunicate with the Cloud one in any way (e.g. users are not shared).
We recommend you to choose this option if you want to setup a "private" LightSchool for your education facility.

### Getting started ###
1. Download the whole project by either downloading it as a ZIP file or by using ```git clone https://github.com/sorge13248/lightschool.git``` in your terminal.
2. Place "public_html" folder content in the root of your webserver (or in a subfolder)
3. Place "secure" folder somewhere on your server not accessible from URL (one level below "localhost" would be perfect)
4. Create an empty database with charset utf8mb4 and assign a user for logging into the database
5. Navigate to the webserver URL in which you placed public_html folders and files
6. The automated install process will take care of the rest
7. Enjoy!
8. (Optional or something is broken) Check if files under config/ folder are set up with the correct values

## Third party library ##
LightSchool uses:
* jquery/jquery
* jquery/jquery-ui
* twbs/bootstrap
* PHPMailer/
* quilljs

## Bug report ##
Found a bug? Use "Issue" tab here on GitHub.
