<?php

class PantryPage extends PantryApp {
    /** @var Twig_Environment $twig */
    protected $twig;

    public function __construct() {
        parent::__construct();
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
            Pantry::$logger->debug($e->getMessage());
            Pantry::$logger->critical("Could not display templates: $filename");
            die();
        }
    }

    private function getTemplateParams($params = []) {
        $init_params = [
            'app_name' => Pantry::$config['app_name'],
            'meta' => [
                'web_root' => Pantry::$web_root,
                'csrf_token' => $this->current_session->getCSRF(),
                'page_tracker' => $this->current_session->getPageTracker()
            ],
            'include' => [
                'css' => [
                    'external' => [],
                    'vendor' => [
                        "bootstrap/bootstrap4.min.css"
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
            'lang' => $this->language,
            'display' => [
                'navbar' => true,
                'navigation' => true,
                'title' => true,
                'footer' => true,
                'user_menu' => true,
                'logged_in' => $this->current_session->isLoggedIn(),
            ],
            'brand' => [
                'label' => Pantry::$config['app_name'],
                'href' => Pantry::$web_root . "/"
            ],
            'navigation' => [
                'left' => [
                    'home' => [
                        'type' => "link",
                        'href' => Pantry::$web_root . "/",
                        'icon' => "fas fa-home",
                        'label' => $this->language['HOME_MENU_BUTTON'],
                        'active' => false
                    ],
                    'recipes' => [
                        'type' => "link",
                        'href' => Pantry::$web_root . "/recipes",
                        'icon' => "fab fa-readme",
                        'label' => "Recipes",
                        'active' => false
                    ]
                ],
                'right' => []
            ],
            'footer' => [
                'year' => date("Y"),
                'name' => "Pantry"
            ]
        ];

        if ($this->current_session->isLoggedIn()) {
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
                                'label' => $this->language['ADMIN_MENU_BUTTON'],
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
                    'label' => $this->language['LOGIN_BUTTON'],
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

    // ========================================
    // Entry points
    // ========================================
    public static function home() {
        $pantry = new self();
        $pantry->current_session->trackPage();

        $params = [
            'title' => $pantry->language['HOME_PAGE_TITLE'],
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
            'title' => $pantry->language['LOGIN_TITLE'],
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

    public static function browseRecipes() {
        $pantry = new self();
        $pantry->current_session->trackPage();

        $params = [
            'title' => "Browse Recipes",
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

    public static function viewRecipe($slug) {
        $pantry = new self();
        $pantry->current_session->trackPage();

        $params = [
            'title' => $pantry->language['VIEW_RECIPE_TITLE'],
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
        $pantry->current_session->trackPage();

        $params = [
            'title' => $pantry->language['EDIT_RECIPE_TITLE'],
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
