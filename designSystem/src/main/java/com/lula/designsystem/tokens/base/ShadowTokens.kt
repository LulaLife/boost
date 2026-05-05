/**
 * This file was generated automatically by Supernova.io and should not be changed manually.
 * To modify the format or content of this file, please contact your design system team. 
 */

package com.lula.designsystem.base

import androidx.compose.runtime.Immutable
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Shadow

@Immutable
object ShadowTokens {
    /** Very subtle shadow used to create minimal depth and separation. */
    val shadowXs = Shadow(color = Color(0x1a000000), offset = Offset(0f, 2f), blurRadius = 4f)
    /** Small shadow that provides a light sense of elevation above the background. */
    val shadowSm = Shadow(color = Color(0x1a000000), offset = Offset(0f, 4f), blurRadius = 6f)
    /** Medium shadow used to clearly separate an element from surrounding surfaces. */
    val shadowMd = Shadow(color = Color(0x1a000000), offset = Offset(0f, 10f), blurRadius = 16f)
    /** Large shadow used for strong elevation and prominent surfaces. */
    val shadowLg = Shadow(color = Color(0x26000000), offset = Offset(0f, 15f), blurRadius = 28f)
    /** Small shadow that provides a light sense of elevation above the background. */
    val shadowSmAi = Shadow(color = Color(0x336839ef), offset = Offset(0f, 8f), blurRadius = 16f)
}