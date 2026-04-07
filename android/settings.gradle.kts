import java.io.File
import java.util.Properties

// AGP JdkImageTransform uses JavaCompiler.installationPath when set; otherwise it falls
// back to System.getProperty("java.home") (the IDE Gradle JVM). Force a real JDK early so
// daemon/tooling and IDE-injected -Dorg.gradle.java.home cannot point at a JRE without jlink.
val eyecareGradleJdk: String? = run {
    val localFile = File(settings.rootDir, "local.properties")
    val fromLocal =
        if (localFile.isFile) {
            Properties().apply { localFile.inputStream().use { load(it) } }
                .getProperty("org.gradle.java.home")?.trim()?.takeIf { it.isNotEmpty() }
        } else {
            null
        }
    val win = File("C:/Program Files/Java/jdk-17")
    val winJlink = File(win, "bin/jlink.exe")
    when {
        fromLocal != null -> fromLocal
        winJlink.isFile -> win.canonicalFile.absolutePath.replace('\\', '/')
        else -> null
    }
}
eyecareGradleJdk?.let { jdk ->
    System.setProperty("org.gradle.java.home", jdk)
}

pluginManagement {
    repositories {
        google {
            content {
                includeGroupByRegex("com\\.android.*")
                includeGroupByRegex("com\\.google.*")
                includeGroupByRegex("androidx.*")
            }
        }
        mavenCentral()
        gradlePluginPortal()
    }
}
dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
    }
}

rootProject.name = "Optical System"
include(":app")
 