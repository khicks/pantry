<?php

class PantryPage extends PantryApp {
    /** @var Twig_Environment $twig */
    protected $twig;

    public function __construct() {
        parent::__construct();
        $this->loadTwig();
    }

    public function loadTwig() {
        $templates[] = Pantry::$php_root."/templates";
        $loader = new Twig_Loader_Filesystem($templates);
        $this->twig = new Twig_Environment($loader);
    }

    public function renderTemplate($filename, $params = []) {
        try {
            return $this->twig->render($filename, $this->getTemplateParams($params));
        }
        catch (Twig_Error $e) {
            Pantry::$logger->critical("Could not render templates: $filename");
            die();
        }
    }

    public function displayTemplate($filename, $params = []) {
        try {
            $this->twig->display($filename, $this->getTemplateParams($params));
        }
        catch (Twig_Error $e) {
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
                    'root' => [
                        "bootstrap.min.css",
                        "pantry.css"
                    ]
                ],
                'js' => [
                    'external' => [],
                    'root' => [
                        "jquery-3.3.1.min.js",
                        "popper.min.js",
                        "bootstrap.min.js",
                        "fontawesome-all.min.js"
                    ]
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
                        'icon' => "home",
                        'label' => $this->language['HOME_MENU_BUTTON'],
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
                    'icon' => "user",
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
                                'icon' => "cogs",
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
                    'icon' => "sign-in-alt",
                    'label' => $this->language['LOGIN_BUTTON'],
                    'active' => false
                ]
            ];
        }

        $page_params = $this->mergeParams($init_params, $params);

        for ($i = 0; $i < count($page_params['include']['css']['root']); $i++) {
            $page_params['include']['css']['root'][$i] = Pantry::$web_root . "/assets/css/{$page_params['include']['css']['root'][$i]}";
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
            'navigation' => [
                'left' => [
                    'home' => [
                        'active' => true
                    ]
                ]
            ]
        ];

        $pantry->displayTemplate("home.html", $params);
    }

    public static function login() {
        $pantry = new self();
        $pantry->requireLogout();

        $params = [
            'title' => $pantry->language['LOGIN_TITLE'],
            'display' => [
                'navigation' => false,
                'title' => false,
                'footer' => false
            ],
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
            ]
        ];

        $pantry->displayTemplate("login.html", $params);
    }
}
