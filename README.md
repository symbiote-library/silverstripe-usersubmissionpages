User Submission Pages
====================================

![Screenshot](http://i.cubeupload.com/I3aBbI.png)

Extends User Defined Forms so that CMS users can create customized listings based on user submissions. Example steps are:
- A CMS user can create a business listing form. 
- Then a user will submit details about their business using the form.
- A CMS user then approves the submission and a page is created, utilizing the submission data.

## Search Form 
This module comes with the ability to enable the various form fields to be searchable.
Simply edit a field and under the 'Search' tab, configure appropriately.

You can also turn this off by setting:
```yaml
UserSubmissionHolder:
	enable_search_form: false
```

This will prevent any search related DB fields from being created on EditableFormField.

## Templating the $Listing
- Copy 'UserSubmissionHolder_Listing.ss' from usersubmissionpages/templates/Includes/UserSubmissionHolder_Listing.ss into your theme/Includes folder.
- /dev/build or ?flush=1
- Modify as you see fit.

## Requirements
- SilverStripe 3.1 or higher
- [User Defined Forms](https://github.com/silverstripe/silverstripe-userforms/tree/3.0) 3.0
- [BetterButtons](https://github.com/unclecheese/silverstripe-gridfield-betterbuttons) 1.2 or higher (lower versions might work fine but are untested)

## Installation
```composer require silbinarywolf/usersubmissionpages:1.1.*```