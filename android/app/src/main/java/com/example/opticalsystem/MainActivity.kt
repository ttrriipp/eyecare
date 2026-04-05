package com.example.opticalsystem

import android.os.Bundle
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.navigation.fragment.NavHostFragment
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import com.example.opticalsystem.util.TokenManager
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.runBlocking
import javax.inject.Inject

@AndroidEntryPoint
class MainActivity : AppCompatActivity() {

    @Inject
    lateinit var tokenManager: TokenManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContentView(R.layout.activity_main)
        // Top/side insets only. Do not pad the root bottom — that leaves a strip above the
        // system nav and makes BottomNavigationView look "floating". Navigation bar inset is
        // applied on BottomNavigationView in MainFragment (and auth screens handle bottom inset).
        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main)) { v, insets ->
            val systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, 0)
            insets
        }

        // Auto-login: choose nav graph start destination before first draw
        // so the login screen doesn't flash when reopening the app.
        if (savedInstanceState == null) {
            val navHostFragment = supportFragmentManager
                .findFragmentById(R.id.nav_host_fragment) as NavHostFragment
            val navController = navHostFragment.navController

            val loggedIn = runBlocking { tokenManager.isLoggedIn() }
            val navGraph = navController.navInflater.inflate(R.navigation.nav_graph)
            // Navigation graph uses `loginFragment -> mainFragment` action when you navigate,
            // but for app reopen we just choose the correct start destination up-front.
            navGraph.setStartDestination(if (loggedIn) R.id.mainFragment else R.id.loginFragment)
            navController.graph = navGraph
        }
    }
}

