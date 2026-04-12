package com.example.opticalsystem.ui.auth

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.compose.ui.platform.ComposeView
import androidx.compose.ui.platform.ViewCompositionStrategy
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.navigation.fragment.findNavController
import com.example.opticalsystem.R
import dagger.hilt.android.AndroidEntryPoint

@AndroidEntryPoint
class LoginFragment : Fragment() {

    private val viewModel: LoginViewModel by viewModels()

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        val backendRootUrl = getString(R.string.backend_root_url)
        val logoPath = getString(R.string.login_logo_public_path)
        val logoUrl = backendRootUrl.trimEnd('/') + "/" + logoPath.trimStart('/')

        return ComposeView(requireContext()).apply {
            setViewCompositionStrategy(ViewCompositionStrategy.DisposeOnViewTreeLifecycleDestroyed)
            setContent {
                EyeCareAuthTheme {
                    LoginScreen(
                        viewModel = viewModel,
                        logoUrl = logoUrl,
                        onNavigateToRegister = {
                            findNavController().navigate(R.id.action_login_to_register)
                        },
                        onNavigateToMain = {
                            findNavController().navigate(R.id.action_login_to_main)
                        },
                    )
                }
            }
        }
    }
}
