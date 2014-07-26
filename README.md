# Job Board Module

## Maintainer Contact

 * Will Rossiter (Nickname: willr, wrossiter) 
   <will (at) fullscreen (dot) io>
	
## Requirements 

 * SilverStripe 3.1

## Installation

```
composer require "fullscreeninteractive/silverstripe-jobboard" "dev-master"
```

After composer has finished doing it's work, make sure you rebuild the database
using `dev/build`.

## Configuration

This module has been setup to work without requiring the CMS module but you can
opt in to use this module with the CMS Module.

### Configuration with the CMS Module

An example of what a page type for the JobBoard would look like if you want to
use it on a single page. You can name your controller methods anyway you wish.

	<?php 

	class JobHolder extends Page {

		private static $extensions = array('JobBoardPageExtension');

	}

	class JobHolder_Controller extends Page_Controller implements IJobBoardController {

		private static $extensions = array('JobBoardPageExtension');

		private static $allowed_actions = array(
			'show',
			'post',
			'edit',
			'delete',
			'thanks',
			'removed'
		);

		public function show() {
			return $this->getJobBoardShowAction();
		}

		public function edit() {
			return $this->getJobBoardEditAction();
		}

		public function post() {
			return $this->getJobBoardPostAction();
		}

		public function delete() {
			return $this->getJobBoardDeleteAction();
		}

		public function thanks() {
			return $this->getJobBoardThanksAction();
		}

		public function removed() {
			return $this->getJobBoardRemoveAction();
		}
	}

If you want to just use the framework module without the CMS simply use the
controller part of that code example.
