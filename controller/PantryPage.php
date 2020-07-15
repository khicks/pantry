<?php

class PantryPage extends PantryApp {
    /** @var Twig_Environment $twig */
    protected $twig;

    public function __construct($install_required = true, $update_required = true) {
        parent::__construct();

        if ($install_required && !Pantry::$installer->getIsInstalled()) {
            $this->redirect("/install");
        }

        if ($install_required && $update_required && !Pantry::$updater->getIsUpdated()) {
            $this->redirect("/update");
        }

        $this->loadTwig();
    }

    private function loadTwig() {
        $templates[] = Pantry::$php_root."/templates";
        $loader = new Twig\Loader\FilesystemLoader($templates);
        $this->twig = new Twig\Environment($loader);
    }

    public function renderTemplate($filename, $params = []) {
        try {
            return $this->twig->render($filename, $this->getTemplateParams($params));
        }
        catch (Twig\Error\Error $e) {
            Pantry::$logger->critical("Could not render templates: $filename");
            die();
        }
    }

    public function displayTemplate($filename, $params = []) {
        try {
            $this->twig->display($filename, $this->getTemplateParams($params));
        }
        catch (Twig\Error\Error $e) {
            Pantry::$logger->error($e->getMessage());
            Pantry::$logger->critical("Could not display templates: $filename");
            die();
        }
    }

    private function getTemplateParams($params = []) {
        $init_params = [
            'app_name' => Pantry::$config->get('app_name'),
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
                        "pantry.css"
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
                    'root' => []
                ]
            ],
            'lang' => $this->language->getAll(),
            'display' => [
                'navbar' => true,
                'navigation' => true,
                'title' => true,
                'footer' => true,
                'user_menu' => true,
                'logged_in' => false,
            ],
            'brand' => [
                'href' => Pantry::$web_root . "/",
                'format' => Pantry::$config->get('app_brand_format'),
            ],
            'navigation' => [
                'left' => [
                    'home' => [
                        'type' => "link",
                        'href' => Pantry::$web_root . "/",
                        'icon' => "fas fa-home",
                        'label' => $this->language->get('HOME_MENU_BUTTON'),
                        'active' => false
                    ],
                    'recipes' => [
                        'type' => "link",
                        'href' => Pantry::$web_root . "/recipes",
                        'icon' => "fab fa-readme",
                        'label' => $this->language->get('RECIPES'),
                        'active' => false
                    ]
                ],
                'right' => []
            ],
            'content' => [],
            'footer' => [
                'year' => date("Y"),
                'name' => "Pantry"
            ]
        ];

        if (!is_null($this->current_session) && $this->current_session->isLoggedIn()) {
            $init_params['display']['logged_in'] = true;
            $init_params['navigation']['left']['create'] = [
                'type' => "link",
                'href' => Pantry::$web_root . "/recipes/create",
                'icon' => "fas fa-plus",
                'label' => $this->language->get('CREATE'),
                'active' => false
            ];

            $init_params['navigation']['right'] = [
                'account' => [
                    'type' => "link",
                    'href' => Pantry::$web_root . "/account",
                    'icon' => "fas fa-user",
                    'label' => $this->current_user->getUsername(),
                    'active' => false
                ]
            ];
            $init_params['include']['js']['root'][] = "logout.js";

            if ($this->current_user->getIsAdmin()) {
                $admin = [
                    'navigation' => [
                        'right' => [
                            'admin' => [
                                'type' => "link",
                                'href' => Pantry::$web_root . "/admin",
                                'icon' => "fas fa-cogs",
                                'label' => $this->language->get('ADMIN_MENU_BUTTON'),
                                'active' => false
                            ]
                        ]
                    ]
                ];
                $init_params = $this->mergeParams($admin, $init_params);
            }
        }
        else {
            $init_params['navigation']['right'] = [
                'account' => [
                    'type' => "link",
                    'href' => Pantry::$web_root . "/login",
                    'icon' => "fas fa-sign-in-alt",
                    'label' => $this->language->get('LOGIN_BUTTON'),
                    'active' => false
                ]
            ];
        }

        $page_params = $this->mergeParams($init_params, $params);

        for ($i = 0; $i < count($page_params['include']['css']['vendor']); $i++) {
            $page_params['include']['css']['vendor'][$i] = Pantry::$web_root . "/assets/css/vendor/{$page_params['include']['css']['vendor'][$i]}";
        }
        for ($i = 0; $i < count($page_params['include']['css']['root']); $i++) {
            $page_params['include']['css']['root'][$i] = Pantry::$web_root . "/assets/css/{$page_params['include']['css']['root'][$i]}";
        }

        for ($i = 0; $i < count($page_params['include']['js']['vendor']); $i++) {
            $page_params['include']['js']['vendor'][$i] = Pantry::$web_root . "/assets/js/vendor/{$page_params['include']['js']['vendor'][$i]}";
        }
        for ($i = 0; $i < count($page_params['include']['js']['root']); $i++) {
            $page_params['include']['js']['root'][$i] = Pantry::$web_root . "/assets/js/{$page_params['include']['js']['root'][$i]}";
        }

        return $page_params;
    }

    protected function mergeParams($init_params, $params) {
        foreach($params as $key => $value) {
            if(array_key_exists($key, $init_params) && is_array($value)) {
                if ($this->isAssocArray($value)) {
                    $init_params[$key] = $this->mergeParams($init_params[$key], $params[$key]);
                }
                else {
                    $init_params[$key] = array_merge($init_params[$key], $params[$key]);
                }
            }
            else {
                $init_params[$key] = $value;
            }
        }
        return $init_params;
    }

    private function isAssocArray($arr) {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    protected function redirect($page) {
        Pantry::$logger->debug("Redirecting user to $page");
        header("Location: ".Pantry::$web_root.$page);
        die();
    }

    protected function requireLogin() {
        if (!$this->current_session->isLoggedIn()) {
            $this->redirect("/login");
        }
    }

    protected function requireLogout() {
        if ($this->current_session->isLoggedIn()) {
            $this->redirect("/");
        }
    }

    protected function requireNotInstalled() {
        if (Pantry::$installer->getIsInstalled()) {
            $this->redirect("/");
        }
    }

    protected function requireNotUpdated() {
        if (Pantry::$updater->getIsUpdated()) {
            $this->redirect("/");
        }
    }

    // ========================================
    // Entry points
    // ========================================
    public static function error404() {
        http_response_code(404);
        $pantry = new self(false);

        $params = [
            'title' => $pantry->language->get('ERROR_404_PAGE_NOT_FOUND'),
            'display' => [
                'title' => false
            ]
        ];

        $pantry->displayTemplate("404.html", $params);
    }

    public static function install() {
        $pantry = new self(false);
        $pantry->requireNotInstalled();

        $params = [
            'title' => $pantry->language->get('INSTALL'),
            'include' => [
                'css' => [
                    'root' => [
                        "install.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "install.js"
                    ]
                ]
            ],
            'display' => [
                'navigation' => false,
                'title' => false
            ]
        ];

        $pantry->displayTemplate("install.html", $params);
    }

    public static function update() {
        $pantry = new self(true, false);
        $pantry->requireNotUpdated();

        $params = [
            'title' => $pantry->language->get('UPDATE'),
            'include' => [
                'css' => [
                    'root' => [
                        "update.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "update.js"
                    ]
                ]
            ],
            'display' => [
                'navigation' => false,
                'title' => false
            ]
        ];

        $pantry->displayTemplate("update.html", $params);
    }

    public static function home() {
        $pantry = new self();
        Pantry::$session->trackPage();

        $params = [
            'title' => $pantry->language->get('HOME_PAGE_TITLE'),
            'include' => [
                'css' => [
                    'root' => [
                        "home.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "home.js"
                    ]
                ]
            ],
            'navigation' => [
                'left' => [
                    'home' => [
                        'active' => true
                    ]
                ]
            ],
            'display' => [
                'title' => false
            ]
        ];

        $pantry->displayTemplate("home.html", $params);
    }

    public static function login() {
        $pantry = new self();
        $pantry->requireLogout();

        $params = [
            'title' => $pantry->language->get('LOGIN_TITLE'),
            'include' => [
                'css' => [
                    'root' => [
                        "login.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "login.js"
                    ]
                ]
            ],
            'display' => [
                'navigation' => false,
                'title' => false
            ]
        ];

        $pantry->displayTemplate("login.html", $params);
    }

    public static function account() {
        $pantry = new self();
        Pantry::$session->trackPage();
        $pantry->requireLogin();

        $params = [
            'title' => $pantry->language->get('MY_ACCOUNT_TITLE'),
            'include' => [
                'css' => [
                    'root' => [
                        "account.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "account.js"
                    ]
                ]
            ],
            'navigation' => [
                'right' => [
                    'account' => [
                        'active' => true
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("account.html", $params);
    }

    public static function browseRecipes() {
        $pantry = new self();
        Pantry::$session->trackPage();

        $params = [
            'title' => "Browse Recipes",
            'include' => [
                'css' => [
                    'root' => [
                        "recipes-browse.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "recipes-browse.js"
                    ]
                ]
            ],
            'navigation' => [
                'left' => [
                    'recipes' => [
                        'active' => true
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("recipes-browse.html", $params);
    }

    public static function createRecipe() {
        $pantry = new self();
        Pantry::$session->trackPage();
        $pantry->requireLogin();

        $params = [
            'title' => $pantry->language->get('CREATE_RECIPE_TITLE'),
            'include' => [
                'css' => [
                    'root' => [
                        "recipes-create.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "recipes-create.js"
                    ]
                ]
            ],
            'display' => [
                'title' => false
            ],
            'navigation' => [
                'left' => [
                    'create' => [
                        'active' => true
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("recipes-create.html", $params);
    }

    public static function viewRecipe($slug) {
        $pantry = new self();
        Pantry::$session->trackPage();

        $params = [
            'title' => $pantry->language->get('VIEW_RECIPE_TITLE'),
            'meta' => [
                'recipe_slug' => $slug,
            ],
            'include' => [
                'css' => [
                    'root' => [
                        "recipes-view.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "recipes-view.js"
                    ]
                ]
            ],
            'display' => [
                'title' => false
            ],
            'navigation' => [
                'left' => [
                    'recipes' => [
                        'active' => true
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("recipes-view.html", $params);
    }

    public static function editRecipe($slug) {
        $pantry = new self();
        Pantry::$session->trackPage();

        $params = [
            'title' => $pantry->language->get('EDIT_RECIPE_TITLE'),
            'meta' => [
                'recipe_slug' => $slug,
            ],
            'include' => [
                'css' => [
                    'root' => [
                        "recipes-edit.css"
                    ]
                ],
                'js' => [
                    'root' => [
                        "recipes-edit.js"
                    ]
                ]
            ],
            'display' => [
                'title' => false
            ],
            'navigation' => [
                'left' => [
                    'recipes' => [
                        'active' => true
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("recipes-edit.html", $params);
    }

    public static function test() {
//        $pantry = new self();
//        echo Pantry::generateUUID();
        echo (preg_match('/^\p{L}*$/', $_GET['a'])) ? "true" : "false";
    }
}
