<?php

class PantryAdminPage extends PantryPage {
    public function __construct() {
        parent::__construct();
        Pantry::$session->trackPage();
        $this->requireAdmin();
        $this->loadTwig();
    }

    private function loadTwig() {
        $templates[] = Pantry::$php_root."/templates/admin";
        $loader = new Twig\Loader\FilesystemLoader($templates);
        $this->twig = new Twig\Environment($loader);
    }

    public function renderTemplate($filename, $params = []) {
        try {
            return $this->twig->render($filename, $this->getTemplateParams($params));
        }
        catch (Twig\Error\Error $e) {
            Pantry::$logger->critical("Could not render template: $filename");
            die();
        }
    }

    public function displayTemplate($filename, $params = []) {
        try {
            $this->twig->display($filename, $this->getTemplateParams($params));
        }
        catch (Twig\Error\Error $e) {
            Pantry::$logger->debug($e->getMessage());
            Pantry::$logger->critical("Could not display template: $filename");
            die();
        }
    }

    private function getTemplateParams($params = []) {
        $init_params = [
            'appname' => Pantry::$config->get('app_name'),
            'meta' => [
                'web_root' => Pantry::$web_root,
                'csrf_token' => Pantry::$session->getCSRF(),
                'page_tracker' => Pantry::$session->getPageTracker()
            ],
            'include' => [
                'css' => [
                    'external' => [],
                    'vendor' => [
                        "bootstrap/bootstrap-4.5.0.min.css"
                    ],
                    'root' => [
                        "admin/admin.css"
                    ]
                ],
                'js' => [
                    'external' => [],
                    'vendor' => [
                        "jquery/jquery-3.5.1.min.js",
                        "popper/popper-1.16.0.min.js",
                        "handlebars/handlebars-4.7.6.min.js",
                        "bootstrap/bootstrap-4.5.0.min.js",
                        "fontawesome/fontawesome-all-5.13.0.min.js"
                    ],
                    'root' => [
                        "admin/admin.js"
                    ]
                ]
            ],
            'lang' => $this->language->getAll(),
            'display' => [
                'alert' => false,
                'screen_size' => true
            ],
            'brand' => [
                'label' => Pantry::$config->get('app_name')." {$this->language->get('ADMIN_BRAND')}",
                'href' => Pantry::$web_root."/admin"
            ],
            'navigation' => [
                'back' => [
                    'label' => "{$this->language->get('ADMIN_BACK')} ".Pantry::$config->get('app_name'),
                    'href' => Pantry::$web_root."/"
                ],
                'sidebar' => [
                    'dashboard' => [
                        'type' => "link",
                        'href' => Pantry::$web_root."/admin",
                        'icon' => "columns",
                        'label' => $this->language->get('ADMIN_DASHBOARD_BUTTON'),
                        'active' => false
                    ],
                    'courses_cuisines' => [
                        'type' => "link",
                        'href' => Pantry::$web_root."/admin/courses-cuisines",
                        'icon' => "utensils",
                        'label' => $this->language->get('ADMIN_COURSES_AND_CUISINES'),
                        'active' => false
                    ],
                    'users' => [
                        'type' => "link",
                        'href' => Pantry::$web_root."/admin/users",
                        'icon' => "users",
                        'label' => $this->language->get('ADMIN_USERS_BUTTON'),
                        'active' => false
                    ]
                ],
                'breadcrumbs' => [
                    'admin' => [
                        'href' => Pantry::$web_root."/admin",
                        'label' => $this->language->get('ADMIN_BREADCRUMBS_ADMIN')
                    ]
                ],
            ],
            'alert' => [],
            'footer' => [
                'year' => date("Y"),
                'name' => "Pantry"
            ]
        ];

        if (isset($_SESSION['alert'])) {
            $init_params['display']['alert'] = true;
            $init_params['alert'] = $_SESSION['alert'];
            unset($_SESSION['alert']);
        }

        $page_params = $this->mergeParams($init_params, $params);

        for ($i=0; $i<count($page_params['include']['css']['vendor']); $i++) {
            $page_params['include']['css']['vendor'][$i] = Pantry::$web_root."/assets/css/vendor/{$page_params['include']['css']['vendor'][$i]}";
        }
        for ($i=0; $i<count($page_params['include']['css']['root']); $i++) {
            $page_params['include']['css']['root'][$i] = Pantry::$web_root."/assets/css/{$page_params['include']['css']['root'][$i]}";
        }

        for ($i=0; $i<count($page_params['include']['js']['vendor']); $i++) {
            $page_params['include']['js']['vendor'][$i] = Pantry::$web_root."/assets/js/vendor/{$page_params['include']['js']['vendor'][$i]}";
        }
        for ($i=0; $i<count($page_params['include']['js']['root']); $i++) {
            $page_params['include']['js']['root'][$i] = Pantry::$web_root."/assets/js/{$page_params['include']['js']['root'][$i]}";
        }

        return $page_params;
    }

    private function requireAdmin() {
        $this->requireLogin();
        if (!$this->current_user->getIsAdmin()) {
            $this->redirect("/");
        }
    }

    public static function dashboard() {
        $pantry = new self();

        $params = [
            'title' => $pantry->language->get('ADMIN_DASHBOARD_TITLE'),
            'include' => [
                'css' => [
                    'root' => [
                        "admin/dashboard.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "admin/dashboard.js"
                    ]
                ]
            ],
            'navigation' => [
                'sidebar' => [
                    'dashboard' => [
                        'active' => true
                    ]
                ],
                'breadcrumbs' => [
                    'dashboard' => [
                        'href' => Pantry::$web_root."/admin",
                        'label' => $pantry->language->get('ADMIN_DASHBOARD_BUTTON')
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("dashboard.html", $params);
    }

    public static function coursesCuisines() {
        $pantry = new self();

        $params = [
            'title' => $pantry->language->get('ADMIN_COURSES_AND_CUISINES'),
            'include' => [
                'css' => [
                    'root' => [
                        "admin/courses-cuisines.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "admin/courses-cuisines.js"
                    ]
                ]
            ],
            'navigation' => [
                'sidebar' => [
                    'courses_cuisines' => [
                        'active' => true
                    ]
                ],
                'breadcrumbs' => [
                    'courses_cuisines' => [
                        'label' => $pantry->language->get('ADMIN_COURSES_AND_CUISINES')
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("courses-cuisines.html", $params);
    }

    public static function users() {
        $pantry = new self();

        $params = [
            'title' => $pantry->language->get('ADMIN_USERS_TITLE'),
            'include' => [
                'css' => [
                    'root' => [
                        "admin/users.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "admin/users.js"
                    ]
                ]
            ],
            'navigation' => [
                'sidebar' => [
                    'users' => [
                        'active' => true
                    ]
                ],
                'breadcrumbs' => [
                    'users' => [
                        'label' => $pantry->language->get('ADMIN_USERS_BUTTON')
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("users.html", $params);
    }

    public static function createUser() {
        $pantry = new self();

        $params = [
            'title' => $pantry->language->get('ADMIN_CREATE_USER_TITLE'),
            'include' => [
                'css' => [
                    'root' => [
                        "admin/admin.css",
                        "admin/users-create.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "admin/admin.js",
                        "admin/users-create.js"
                    ]
                ]
            ],
            'navigation' => [
                'sidebar' => [
                    'users' => [
                        'active' => true
                    ]
                ],
                'breadcrumbs' => [
                    'users' => [
                        'href' => Pantry::$web_root."/admin/users",
                        'label' => $pantry->language->get('ADMIN_USERS_BUTTON')
                    ],
                    'users-create' => [
                        'label' => $pantry->language->get('ADMIN_CREATE_USER_BREADCRUMBS')
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("users-create.html", $params);
    }

    public static function editUser($username) {
        $pantry = new self();

        try {
            $user_id = PantryUser::lookupUsername($username);
            if ($user_id === false) {
                throw new PantryUserNotFoundException("User not found.");
            }
            $user = new PantryUser($user_id);
        }
        catch (PantryUserNotFoundException $e) {
            $pantry->redirect('/admin/users');
            die();
        }

        $params = [
            'title' => $pantry->language->get('ADMIN_EDIT_USER_TITLE'),
            'meta' => [
                'user_id' => $user->getId()
            ],
            'include' => [
                'css' => [
                    'root' => [
                        "admin/admin.css",
                        "admin/users-edit.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "admin/admin.js",
                        "admin/users-edit.js"
                    ]
                ]
            ],
            'navigation' => [
                'sidebar' => [
                    'users' => [
                        'active' => true
                    ]
                ],
                'breadcrumbs' => [
                    'users' => [
                        'href' => Pantry::$web_root."/admin/users",
                        'label' => $pantry->language->get('ADMIN_USERS_BUTTON')
                    ],
                    'users-create' => [
                        'label' => $user->getDisplayName()
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("users-edit.html", $params);
    }
}
