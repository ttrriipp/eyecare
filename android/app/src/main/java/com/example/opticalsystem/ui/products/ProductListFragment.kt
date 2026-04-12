package com.example.opticalsystem.ui.products

import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.text.Editable
import android.text.TextWatcher
import android.view.LayoutInflater
import android.view.Menu
import android.view.MenuItem
import android.view.View
import android.view.ViewGroup
import android.widget.PopupMenu
import android.widget.Toast
import android.text.TextUtils
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.fragment.app.viewModels
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.lifecycleScope
import androidx.lifecycle.repeatOnLifecycle
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.GridLayoutManager
import com.example.opticalsystem.R
import com.example.opticalsystem.data.model.ProductCategory
import com.example.opticalsystem.databinding.FragmentProductListBinding
import com.example.opticalsystem.util.Resource
import androidx.core.content.ContextCompat
import com.google.android.material.chip.Chip
import dagger.hilt.android.AndroidEntryPoint
import kotlinx.coroutines.launch

@AndroidEntryPoint
class ProductListFragment : Fragment() {
    private data class SortOption(
        val label: String,
        val sortBy: String?,
        val sortDir: String?,
    )

    private var _binding: FragmentProductListBinding? = null
    private val binding get() = _binding!!

    private val viewModel: ProductListViewModel by viewModels()
    private lateinit var productAdapter: ProductAdapter

    private val searchHandler = Handler(Looper.getMainLooper())
    private var searchRunnable: Runnable? = null
    private var selectedSort: SortOption? = null

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?,
    ): View {
        _binding = FragmentProductListBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupRecyclerView()
        setupSearch()
        setupSortControl()
        setupCartButton()
        observeCartBadge()
        observeViewModel()
    }

    private fun setupCartButton() {
        binding.btnCart.setOnClickListener {
            findNavController().navigate(R.id.action_nav_explore_to_cart)
        }
    }

    private fun observeCartBadge() {
        viewLifecycleOwner.lifecycleScope.launch {
            viewLifecycleOwner.repeatOnLifecycle(Lifecycle.State.STARTED) {
                viewModel.cartItemCount.collect { count ->
                    val badgeCount = count.coerceAtMost(99)
                    binding.tvCartBadge.isVisible = count > 0
                    if (count > 0) {
                        binding.tvCartBadge.text = if (count > 99) "99+" else badgeCount.toString()
                    }
                }
            }
        }
    }

    override fun onResume() {
        super.onResume()
        // Ensure Catalog list reflects latest backend updates when returning from details.
        viewModel.refreshProducts()
    }

    private fun setupRecyclerView() {
        productAdapter = ProductAdapter { product ->
            findNavController().navigate(
                R.id.action_nav_explore_to_productDetail,
                Bundle().apply { putInt("productId", product.id) },
            )
        }
        binding.rvProducts.apply {
            val gridLayoutManager = GridLayoutManager(requireContext(), 2)
            gridLayoutManager.spanSizeLookup = object : GridLayoutManager.SpanSizeLookup() {
                override fun getSpanSize(position: Int): Int {
                    return if (productAdapter.isFullSpan(position)) 2 else 1
                }
            }
            layoutManager = gridLayoutManager
            adapter = productAdapter
            setHasFixedSize(true)
        }
    }

    private fun setupSearch() {
        binding.etSearch.addTextChangedListener(object : TextWatcher {
            override fun beforeTextChanged(s: CharSequence?, start: Int, count: Int, after: Int) = Unit
            override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) = Unit
            override fun afterTextChanged(s: Editable?) {
                searchRunnable?.let { searchHandler.removeCallbacks(it) }
                searchRunnable = Runnable {
                    viewModel.applySearch(s?.toString())
                }.also { searchHandler.postDelayed(it, 500) }
            }
        })
    }

    private fun setupSortControl() {
        selectedSort = SortOption(
            label = getString(R.string.sort_popular),
            sortBy = null,
            sortDir = null,
        )
        renderSortLabel()

        binding.tvSortBy.setOnClickListener { anchor ->
            val options = listOf(
                SortOption(getString(R.string.sort_popular), null, null),
                SortOption(getString(R.string.sort_price_low_high), "price", "asc"),
                SortOption(getString(R.string.sort_price_high_low), "price", "desc"),
                SortOption(getString(R.string.sort_name_az), "name", "asc"),
                SortOption(getString(R.string.sort_name_za), "name", "desc"),
            )

            val popup = PopupMenu(requireContext(), anchor)
            options.forEachIndexed { index, option ->
                popup.menu.add(Menu.NONE, index, index, option.label)
            }

            popup.setOnMenuItemClickListener { item: MenuItem ->
                val option = options.getOrNull(item.itemId) ?: return@setOnMenuItemClickListener false
                selectedSort = option
                renderSortLabel()
                viewModel.applySort(option.sortBy, option.sortDir)
                true
            }

            popup.show()
        }
    }

    private fun observeViewModel() {
        viewModel.categories.observe(viewLifecycleOwner) { result ->
            if (result is Resource.Success) {
                addCategoryChips(result.data)
            }
        }

        viewModel.products.observe(viewLifecycleOwner) { result ->
            when (result) {
                is Resource.Loading -> {
                    binding.progressBar.isVisible = true
                    binding.rvProducts.isVisible = false
                }
                is Resource.Success -> {
                    binding.progressBar.isVisible = false
                    binding.rvProducts.isVisible = true
                    productAdapter.submitProducts(result.data)
                }
                is Resource.Error -> {
                    binding.progressBar.isVisible = false
                    binding.rvProducts.isVisible = true
                    Toast.makeText(requireContext(), result.message, Toast.LENGTH_LONG).show()
                }
            }
        }
    }

    private fun addCategoryChips(categories: List<ProductCategory>) {
        // Remove all existing chips except "All" (chipAll is at index 0)
        val chipGroup = binding.chipGroupCategories
        val keepAll = chipGroup.getChildAt(0)
        chipGroup.removeAllViews()
        chipGroup.addView(keepAll)
        configureCategoryChip(binding.chipAll)

        // Re-attach "All" chip listener
        binding.chipAll.setOnCheckedChangeListener { _, isChecked ->
            if (isChecked) viewModel.applyCategory(null)
        }

        categories.forEach { category ->
            val chip = Chip(requireContext()).apply {
                id = category.id
                text = category.name
                isCheckable = true
                isCheckedIconVisible = false
                configureCategoryChip(this)

                setOnCheckedChangeListener { _, isChecked ->
                    if (isChecked) viewModel.applyCategory(category.id)
                }
            }
            chipGroup.addView(chip)
        }
    }

    private fun configureCategoryChip(chip: Chip) {
        val chipHeight = resources.getDimensionPixelSize(R.dimen.category_chip_height)
        val horizontalPadding = resources.getDimension(R.dimen.category_chip_horizontal_padding)

        chip.setEnsureMinTouchTargetSize(false)
        chip.minHeight = chipHeight
        chip.chipMinHeight = chipHeight.toFloat()
        chip.chipStartPadding = horizontalPadding
        chip.chipEndPadding = horizontalPadding
        chip.chipStrokeWidth = resources.getDimension(R.dimen.chip_stroke_width_dp)
        chip.chipBackgroundColor = ContextCompat.getColorStateList(requireContext(), R.color.chip_background_color)
        chip.setTextColor(ContextCompat.getColorStateList(requireContext(), R.color.chip_text_color))
        chip.chipStrokeColor = ContextCompat.getColorStateList(requireContext(), R.color.chip_stroke_color)
        chip.isSingleLine = true
        chip.ellipsize = TextUtils.TruncateAt.END
    }

    private fun renderSortLabel() {
        val label = selectedSort?.label ?: getString(R.string.sort_popular)
        binding.tvSortBy.text = getString(R.string.sort_by_label_format, label)
    }

    override fun onDestroyView() {
        searchRunnable?.let { searchHandler.removeCallbacks(it) }
        super.onDestroyView()
        _binding = null
    }
}
